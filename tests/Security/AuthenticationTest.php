<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\IndexerTestCase;
use Ivfi\Tests\Support\Response;
use Ivfi\Tests\Support\Server;

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
