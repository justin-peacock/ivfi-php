<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\IndexerTestCase;
use Ivfi\Tests\Support\Response;
use Ivfi\Tests\Support\Server;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Session authentication, which replaced HTTP digest.
 *
 * Digest fixed the hash to MD5, needed a password equivalent on disk, could
 * not be signed out of, and re-authenticated on every request. These cover the
 * replacement end to end through PHP's built-in server, so the exchange uses
 * the real cookies and the real form token rather than reconstructed ones.
 */
final class AuthenticationTest extends IndexerTestCase
{
    private const USER = 'emy';
    private const PASS = 'correct horse battery staple';

    /** @var list<Server> */
    private array $servers = [];

    protected function tearDown(): void
    {
        foreach ($this->servers as $server) {
            $server->stop();
        }

        $this->servers = [];
    }

    private function serve(?string $credential = null, array $extra = []): Server
    {
        $fixture = new Fixture('session-auth');
        $fixture->file('private.jpg');
        $fixture->config([
            'authentication' => array_merge([
                'users' => [
                    self::USER => $credential ?? password_hash(self::PASS, PASSWORD_DEFAULT),
                ],
                /* Kept inside the fixture so it is torn down with it */
                'throttle_path' => $fixture->root(),
            ], $extra),
        ]);

        $server = new Server($fixture);
        $this->servers[] = $server;

        return $server;
    }

    private function token(Response $response): string
    {
        preg_match('#name="ivfi_csrf" value="([^"]+)"#', $response->body, $m);

        $this->assertNotEmpty($m[1] ?? '', 'the login form carried no token');

        return $m[1];
    }

    private function signIn(Server $server, string $password): Response
    {
        $login = $server->request('/');

        return $server->request('/', [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => $password,
            'ivfi_csrf' => $this->token($login),
        ]);
    }

    public function testUnauthenticatedRequestGetsTheLoginPage(): void
    {
        $response = $this->serve()->request('/');

        $this->assertSame('401 Unauthorized', $response->header('Status'));
        $this->assertStringContainsString('name="ivfi_pass"', $response->body);
    }

    /**
     * The point of the gate: nothing about the directory may leak alongside
     * the login form.
     */
    public function testLoginPageDoesNotLeakTheListing(): void
    {
        $response = $this->serve()->request('/');

        $this->assertStringNotContainsString('private.jpg', $response->body);
        $this->assertStringNotContainsString('<tr class="file">', $response->body);
    }

    public function testCorrectPasswordSignsIn(): void
    {
        $server = $this->serve();

        $this->assertSame('302 Found', $this->signIn($server, self::PASS)->header('Status'));

        $listing = $server->request('/');

        $this->assertStringContainsString('private.jpg', $listing->body);
    }

    public function testWrongPasswordDoesNotSignIn(): void
    {
        $server = $this->serve();

        $result = $this->signIn($server, 'wrong');

        $this->assertStringContainsString('Incorrect username or password', $result->body);
        $this->assertStringNotContainsString('private.jpg', $server->request('/')->body);
    }

    /**
     * An unknown user and a wrong password must be indistinguishable, or the
     * form becomes a way to enumerate accounts.
     */
    public function testUnknownUserAndWrongPasswordLookTheSame(): void
    {
        $server = $this->serve();

        $wrongPassword = $this->signIn($server, 'wrong');

        $server->clearCookies();

        $login = $server->request('/');
        $unknownUser = $server->request('/', [], [
            'ivfi_user' => 'nobody',
            'ivfi_pass' => 'wrong',
            'ivfi_csrf' => $this->token($login),
        ]);

        $this->assertStringContainsString('Incorrect username or password', $wrongPassword->body);
        $this->assertStringContainsString('Incorrect username or password', $unknownUser->body);
        $this->assertSame($wrongPassword->header('Status'), $unknownUser->header('Status'));
    }

    public function testSessionCookieIsHttpOnlyAndSameSite(): void
    {
        $attributes = $this->serve()->request('/')->cookieAttributes('IVFISESS');

        $this->assertContains('httponly', $attributes, 'the session cookie is readable by script');
        $this->assertContains('samesite=lax', $attributes);
        $this->assertContains('path=/', $attributes);
    }

    /**
     * The identifier must change on sign-in, or one fixed beforehand stays
     * valid afterwards.
     */
    public function testSessionIdentifierChangesOnSignIn(): void
    {
        $server = $this->serve();

        $before = $server->request('/')->setCookies()['IVFISESS'] ?? '';
        $after = $this->signIn($server, self::PASS)->setCookies()['IVFISESS'] ?? '';

        $this->assertNotSame('', $before);
        $this->assertNotSame('', $after);
        $this->assertNotSame($before, $after, 'the session identifier was reused');
    }

    public function testFormWithoutAValidTokenIsRejected(): void
    {
        $server = $this->serve();

        $server->request('/');

        $result = $server->request('/', [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => 'not-the-token',
        ]);

        $this->assertStringContainsString('session expired', $result->body);
        $this->assertStringNotContainsString('private.jpg', $server->request('/')->body);
    }

    public function testSignOutEndsTheSession(): void
    {
        $server = $this->serve();

        $this->signIn($server, self::PASS);

        $listing = $server->request('/');

        preg_match('#href="\?ivfi_logout=([^"]+)"#', $listing->body, $m);

        $this->assertNotEmpty($m[1] ?? '', 'no sign-out link was rendered');

        $server->request('/?ivfi_logout=' . $m[1]);

        $after = $server->request('/');

        $this->assertSame('401 Unauthorized', $after->header('Status'));
        $this->assertStringNotContainsString('private.jpg', $after->body);
    }

    /**
     * Signing out is a state change, so a link from somewhere else must not
     * be able to do it.
     */
    public function testSignOutNeedsTheSessionToken(): void
    {
        $server = $this->serve();

        $this->signIn($server, self::PASS);
        $server->request('/?ivfi_logout=guessed');

        $this->assertStringContainsString('private.jpg', $server->request('/')->body);
    }

    public function testRepeatedFailuresLockTheAddressOut(): void
    {
        $server = $this->serve();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->signIn($server, 'wrong');
        }

        $locked = $this->signIn($server, 'wrong');

        $this->assertSame('429 Too Many Requests', $locked->header('Status'));
        $this->assertStringContainsString('Too many attempts', $locked->body);
    }

    /**
     * The counter is keyed on the address alone. Were the username part of the
     * key, one address could try a common password against a list of names and
     * never trip a lockout, because each name would carry its own count.
     */
    public function testVaryingTheUsernameDoesNotEvadeTheLockout(): void
    {
        $server = $this->serve();

        $names = ['alice', 'bob', 'carol', 'dave', 'erin'];

        foreach ($names as $name) {
            $login = $server->request('/');

            $server->request('/', [], [
                'ivfi_user' => $name,
                'ivfi_pass' => 'wrong',
                'ivfi_csrf' => $this->token($login),
            ]);
        }

        $result = $this->signIn($server, self::PASS);

        $this->assertSame(
            '429 Too Many Requests',
            $result->header('Status'),
            'spraying different usernames from one address avoided the lockout'
        );
    }

    /**
     * Destroying the session server-side is not enough on its own: without
     * expiring the cookie the browser keeps presenting the old identifier.
     */
    public function testSignOutExpiresTheSessionCookie(): void
    {
        $server = $this->serve();

        $this->signIn($server, self::PASS);

        $listing = $server->request('/');

        preg_match('#href="\?ivfi_logout=([^"]+)"#', $listing->body, $m);

        $this->assertNotEmpty($m[1] ?? '');

        $logout = $server->request('/?ivfi_logout=' . $m[1]);

        $attributes = $logout->cookieAttributes('IVFISESS');

        $this->assertNotEmpty($attributes, 'sign-out set no cookie at all');

        $expiry = '';

        foreach ($attributes as $attribute) {
            if (strpos($attribute, 'expires=') === 0) {
                $expiry = substr($attribute, 8);
            }
        }

        $this->assertNotSame('', $expiry, 'the sign-out cookie carried no expiry');
        $this->assertLessThan(
            time(),
            strtotime($expiry),
            'the sign-out cookie was not expired'
        );
    }

    /**
     * A lockout that the right password walks through is not a lockout.
     */
    public function testLockoutAlsoRefusesTheCorrectPassword(): void
    {
        $server = $this->serve();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->signIn($server, 'wrong');
        }

        $result = $this->signIn($server, self::PASS);

        $this->assertSame('429 Too Many Requests', $result->header('Status'));
        $this->assertStringNotContainsString('private.jpg', $server->request('/')->body);
    }

    /**
     * A plaintext password in the config would work silently and leave the
     * secret readable on disk, so it is refused instead.
     */
    public function testPlaintextCredentialIsRefused(): void
    {
        $server = $this->serve('just-a-plain-password');

        $response = $server->request('/');

        $this->assertSame('500 Internal Server Error', $response->header('Status'));
        $this->assertStringContainsString('misconfigured', $response->body);

        $result = $this->signIn($server, 'just-a-plain-password');

        $this->assertStringNotContainsString('private.jpg', $result->body);
    }

    /**
     * The redirect after signing in is built from the request target, which is
     * the request line as the client wrote it. `//evil.example/` survives
     * unaltered and is a protocol relative URL, so echoing it back would bounce
     * the visitor off-site immediately after they authenticated on the real
     * domain, which is a ready-made phishing hop.
     *
     */
    #[DataProvider('offsiteTargets')]
    public function testSignInRedirectCannotLeaveTheSite(string $target): void
    {
        $server = $this->serve();

        $login = $server->request('/');

        $result = $server->request($target, [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $this->token($login),
        ]);

        $location = (string) $result->header('Location');

        $this->assertDoesNotMatchRegularExpression(
            '#^(?://|https?:)#i',
            $location,
            sprintf('signing in at %s redirected off-site to %s', $target, $location)
        );
    }

    #[DataProvider('offsiteTargets')]
    public function testSignOutRedirectCannotLeaveTheSite(string $target): void
    {
        $server = $this->serve();

        $this->signIn($server, self::PASS);

        $result = $server->request(rtrim($target, '/') . '/?ivfi_logout=x');

        $location = (string) $result->header('Location');

        $this->assertDoesNotMatchRegularExpression(
            '#^(?://|https?:)#i',
            $location,
            sprintf('signing out at %s redirected off-site to %s', $target, $location)
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public static function offsiteTargets(): array
    {
        return [
            'protocol relative'   => ['//evil.example/'],
            'absolute form'       => ['https://evil.example/x'],
            'backslash authority' => ['/\\evil.example/'],
            'triple slash'        => ['///evil.example/'],
        ];
    }

    /**
     * The guard must not cost an ordinary redirect its destination.
     */
    public function testSignInReturnsToTheRequestedPath(): void
    {
        $server = $this->serve();

        $server->request('/');
        $login = $server->request('/');

        $result = $server->request('/photos/?sort=name', [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $this->token($login),
        ]);

        $this->assertSame('/photos/?sort=name', $result->header('Location'));
    }

    /**
     * The `restrict` regex still decides which paths are gated at all.
     */
    public function testRestrictLeavesOtherPathsOpen(): void
    {
        $server = $this->serve(null, ['restrict' => '#^/locked#']);

        $this->assertStringContainsString(
            'private.jpg',
            $server->request('/')->body,
            'an unrestricted path asked for a login'
        );
    }
}
