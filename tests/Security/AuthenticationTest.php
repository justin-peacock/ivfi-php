<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
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

    private function serve(
        ?string $credential = null,
        array $extra = [],
        array $ini = []
    ): Server {
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

        $server = new Server($fixture, $ini);
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
     * Behind a proxy every request carries the proxy's address in REMOTE_ADDR,
     * so a counter keyed on it treats the whole internet as one client: five
     * bad attempts from anyone locks out everyone. When the operator says a
     * proxy is in front, the forwarded address decides instead.
     */
    public function testForwardedAddressesAreThrottledSeparately(): void
    {
        $server = $this->serve(null, [
            'behind_proxy' => true,
            'client_ip_header' => 'CF-Connecting-IP',
        ]);

        /* One address burns through its allowance */
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $login = $server->request('/', ['CF-Connecting-IP' => '198.51.100.10']);

            $server->request('/', ['CF-Connecting-IP' => '198.51.100.10'], [
                'ivfi_user' => self::USER,
                'ivfi_pass' => 'wrong',
                'ivfi_csrf' => $this->token($login),
            ]);
        }

        $server->clearCookies();

        /* A different one must be unaffected */
        $login = $server->request('/', ['CF-Connecting-IP' => '203.0.113.20']);
        $other = $server->request('/', ['CF-Connecting-IP' => '203.0.113.20'], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $this->token($login),
        ]);

        $this->assertSame(
            '302 Found',
            $other->header('Status'),
            "one visitor's failures locked out everybody behind the proxy"
        );
    }

    /**
     * The forwarded address is only believed when the operator opts in, since
     * any client can send the header.
     */
    public function testForwardedAddressIsIgnoredWithoutOptIn(): void
    {
        $server = $this->serve();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $login = $server->request('/', ['X-Forwarded-For' => '198.51.100.' . $attempt]);

            $server->request('/', ['X-Forwarded-For' => '198.51.100.' . $attempt], [
                'ivfi_user' => self::USER,
                'ivfi_pass' => 'wrong',
                'ivfi_csrf' => $this->token($login),
            ]);
        }

        $result = $this->signIn($server, self::PASS);

        $this->assertSame(
            '429 Too Many Requests',
            $result->header('Status'),
            'a spoofed forwarded address was enough to reset the counter'
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
     * session_set_cookie_params() and session_name() only work before a session
     * exists. Called while one is already active they warn and return false,
     * and the cookie silently keeps PHP's defaults: no HttpOnly, no SameSite,
     * and the wrong name. Every protection here, quietly off.
     */
    public function testCookieStaysHardenedWhenASessionAutoStarts(): void
    {
        $server = $this->serve(null, [], ['session.auto_start' => '1']);

        $attributes = $server->request('/')->cookieAttributes('IVFISESS');

        $this->assertNotEmpty(
            $attributes,
            'no IVFISESS cookie was set, so the session name was lost'
        );
        $this->assertContains('httponly', $attributes);
        $this->assertContains('samesite=lax', $attributes);
    }

    /**
     * A chain of two proxies sends `https, http`, where the leftmost entry is
     * what the original client used.
     */
    public function testChainedForwardedProtoIsUnderstood(): void
    {
        $server = $this->serve(null, ['behind_proxy' => true]);

        $attributes = $server->request('/', [
            'X-Forwarded-Proto' => 'https, http',
        ])->cookieAttributes('IVFISESS');

        $this->assertContains(
            'secure',
            $attributes,
            'the cookie was issued without Secure on an HTTPS request'
        );
    }

    /**
     * The sign-out token is rendered into a link, so it reaches history and
     * access logs. It must not be the token that guards the login form.
     */
    public function testSignOutTokenIsNotTheFormToken(): void
    {
        $server = $this->serve();

        $this->signIn($server, self::PASS);

        preg_match('#href="\?ivfi_logout=([^"]+)"#', $server->request('/')->body, $m);

        $logoutToken = $m[1] ?? '';

        $this->assertNotEmpty($logoutToken);

        $server->request('/?ivfi_logout=' . $logoutToken);
        $server->request('/');

        /* The leaked value must be useless against the form */
        $result = $server->request('/', [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $logoutToken,
        ]);

        $this->assertStringContainsString(
            'session expired',
            $result->body,
            'the sign-out token was accepted as the login form token'
        );
    }

    /**
     * The counter file must not be a fixed name in a shared directory, where
     * another local user can create it unwritable in advance and turn
     * throttling off for good.
     */
    public function testThrottleFileIsPerInstallationAndPrivate(): void
    {
        $fixture = new Fixture('throttle-file');
        $fixture->file('private.jpg');
        $fixture->config([
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
        ]);

        $server = new Server($fixture);
        $this->servers[] = $server;

        $login = $server->request('/');
        $server->request('/', [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => 'wrong',
            'ivfi_csrf' => $this->token($login),
        ]);

        $files = glob($fixture->root() . '/ivfi-auth*.json');

        $this->assertNotEmpty($files, 'no throttle file was written');
        $this->assertNotSame(
            $fixture->root() . '/ivfi-auth.json',
            $files[0],
            'the throttle file uses a fixed, predictable name'
        );
        $this->assertSame(
            '0600',
            substr(sprintf('%o', fileperms($files[0])), -4),
            'the throttle file is readable by other users'
        );
    }

    /**
     * With the flat credential form the options sit in the same array, so a
     * misplaced option key would otherwise be read as a username and reported
     * as a password hash problem, pointing nowhere near the mistake.
     */
    public function testMisplacedOptionKeyIsNamed(): void
    {
        $fixture = new Fixture('flat-options');
        $fixture->file('private.jpg');
        $fixture->config([
            'authentication' => [
                self::USER => password_hash(self::PASS, PASSWORD_DEFAULT),
                'behind_proxy' => true,
            ],
        ]);

        $response = Indexer::render($fixture);

        $this->assertStringNotContainsString('private.jpg', $response->body);

        /**
         * Both forms refuse the config. What matters is that the log points at
         * the actual mistake rather than reporting a password hash problem
         * about a key that was never meant to be a credential
         */
        $this->assertStringContainsString(
            'behind_proxy',
            $response->stderr,
            'the log did not name the misplaced key'
        );
        $this->assertStringContainsString('is an authentication option', $response->stderr);
    }

    /**
     * Nothing stops somebody having a user named after one of the options. A
     * hash is a hash whatever the key is called, and under the nested form
     * there is no ambiguity to resolve.
     */
    public function testUsernameMatchingAnOptionNameStillWorks(): void
    {
        $fixture = new Fixture('option-named-user');
        $fixture->file('private.jpg');
        $fixture->config([
            'authentication' => [
                'users' => ['restrict' => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
        ]);

        $server = new Server($fixture);
        $this->servers[] = $server;

        $login = $server->request('/');

        $this->assertStringContainsString(
            'name="ivfi_pass"',
            $login->body,
            'a user named after an option was reported as a misconfiguration'
        );

        $result = $server->request('/', [], [
            'ivfi_user' => 'restrict',
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $this->token($login),
        ]);

        $this->assertSame('302 Found', $result->header('Status'));
        $this->assertStringContainsString('private.jpg', $server->request('/')->body);
    }

    /**
     * The unknown-user path verifies against a configured hash, so it has to
     * keep working whatever algorithm the operator chose.
     */
    public function testUnknownUserIsRejectedWithArgonCredentials(): void
    {
        if (!defined('PASSWORD_ARGON2ID')) {
            $this->markTestSkipped('argon2id is not available in this build');
        }

        $server = $this->serve(password_hash(self::PASS, PASSWORD_ARGON2ID));

        $login = $server->request('/');
        $result = $server->request('/', [], [
            'ivfi_user' => 'nobody',
            'ivfi_pass' => 'wrong',
            'ivfi_csrf' => $this->token($login),
        ]);

        $this->assertStringContainsString('Incorrect username or password', $result->body);
        $this->assertStringNotContainsString('private.jpg', $result->body);
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
