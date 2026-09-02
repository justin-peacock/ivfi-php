<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\IndexerTestCase;
use Ivfi\Tests\Support\Response;
use Ivfi\Tests\Support\Server;

/**
 * Digest authentication compared the response with a loose, non constant time
 * `!=`, and issued nonces from `uniqid()` that were never checked when the
 * client sent them back.
 *
 * These run through PHP's built-in server so the real challenge can be read
 * from the response headers, which means the exchange uses the server's own
 * nonce rather than one the test reconstructed.
 */
final class AuthenticationTest extends IndexerTestCase
{
    private const USER  = 'alice';
    private const PASS  = 's3cret';
    private const REALM = 'Restricted content.';

    /** @var list<Server> */
    private array $servers = [];

    protected function tearDown(): void
    {
        foreach ($this->servers as $server) {
            $server->stop();
        }

        $this->servers = [];
    }

    private function serve(Fixture $fixture): Server
    {
        $server = new Server($fixture);
        $this->servers[] = $server;

        return $server;
    }

    private function fixture(?string $credential = null): Fixture
    {
        $fixture = new Fixture('auth');
        $fixture->file('secret.jpg');
        $fixture->config([
            'authentication' => [
                'users' => [self::USER => $credential ?? self::PASS],
            ],
        ]);

        return $fixture;
    }

    /**
     * Reads the nonce out of a fresh challenge.
     */
    private function challenge(Server $server): string
    {
        $response = $server->request('/');

        $header = (string) $response->header('WWW-Authenticate');

        $this->assertMatchesRegularExpression('/^Digest /', $header, 'no digest challenge issued');
        $this->assertSame('401 Unauthorized', $response->header('Status'));

        preg_match('/nonce="([^"]+)"/', $header, $m);

        $this->assertNotEmpty($m[1] ?? '', 'challenge carried no nonce');

        return $m[1];
    }

    private function authorize(
        Server $server,
        string $nonce,
        string $password,
        string $user = self::USER,
        string $uri = '/'
    ): Response {
        $cnonce = 'testcnonce';
        $nc     = '00000001';

        $a1 = md5($user . ':' . self::REALM . ':' . $password);
        $a2 = md5('GET:' . $uri);

        $response = md5("{$a1}:{$nonce}:{$nc}:{$cnonce}:auth:{$a2}");

        return $server->request($uri, [
            'Authorization' => sprintf(
                'Digest username="%s", realm="%s", nonce="%s", uri="%s", qop=auth, nc=%s, cnonce="%s", response="%s"',
                $user,
                self::REALM,
                $nonce,
                $uri,
                $nc,
                $cnonce,
                $response
            ),
        ]);
    }

    public function testUnauthenticatedRequestIsChallenged(): void
    {
        $server = $this->serve($this->fixture());

        $this->challenge($server);
    }

    public function testCorrectPasswordIsAccepted(): void
    {
        $server = $this->serve($this->fixture());

        $result = $this->authorize($server, $this->challenge($server), self::PASS);

        $this->assertStringContainsString('secret.jpg', $result->body);
        $this->assertStringNotContainsString('Invalid credentials', $result->body);
    }

    public function testWrongPasswordIsRejected(): void
    {
        $server = $this->serve($this->fixture());

        $result = $this->authorize($server, $this->challenge($server), 'wrong');

        $this->assertStringContainsString('Invalid credentials', $result->body);
        $this->assertStringNotContainsString('secret.jpg', $result->body);
    }

    public function testUnknownUserIsRejected(): void
    {
        $server = $this->serve($this->fixture());

        $result = $this->authorize(
            $server, $this->challenge($server), self::PASS, 'mallory'
        );

        $this->assertStringContainsString('Invalid credentials', $result->body);
    }

    /**
     * A credential may be stored as a precomputed HA1 so that a deployment
     * does not have to keep the password in the clear.
     */
    public function testPrecomputedHa1CredentialIsAccepted(): void
    {
        $ha1 = md5(self::USER . ':' . self::REALM . ':' . self::PASS);

        $server = $this->serve($this->fixture('md5:' . $ha1));

        $result = $this->authorize($server, $this->challenge($server), self::PASS);

        $this->assertStringContainsString('secret.jpg', $result->body);
    }

    public function testPrecomputedHa1CredentialStillRejectsWrongPassword(): void
    {
        $ha1 = md5(self::USER . ':' . self::REALM . ':' . self::PASS);

        $server = $this->serve($this->fixture('md5:' . $ha1));

        $result = $this->authorize($server, $this->challenge($server), 'wrong');

        $this->assertStringContainsString('Invalid credentials', $result->body);
    }

    /**
     * The nonce used to be `uniqid()` and was never validated, so a client
     * could invent one. It must now be a challenge the server actually issued.
     */
    public function testInventedNonceIsRejected(): void
    {
        $server = $this->serve($this->fixture());

        $result = $this->authorize($server, uniqid(), self::PASS);

        $this->assertStringContainsString('Invalid credentials', $result->body);
        $this->assertStringNotContainsString('secret.jpg', $result->body);
    }

    public function testForgedNonceWithPlausibleShapeIsRejected(): void
    {
        $server = $this->serve($this->fixture());

        $forged = time() . ':' . bin2hex(random_bytes(8)) . ':' . str_repeat('a', 64);

        $result = $this->authorize($server, $forged, self::PASS);

        $this->assertStringContainsString('Invalid credentials', $result->body);
    }

    /**
     * Each challenge is distinct, so a captured one cannot simply be reused
     * forever.
     */
    public function testEachChallengeIssuesADistinctNonce(): void
    {
        $server = $this->serve($this->fixture());

        $this->assertNotSame(
            $this->challenge($server),
            $this->challenge($server),
            'the same nonce was issued twice'
        );
    }

    /**
     * The status line was written without a space, producing the malformed
     * `HTTP/1.1401 Unauthorized`.
     */
    public function testChallengeSendsAWellFormedStatus(): void
    {
        $server = $this->serve($this->fixture());

        $response = $server->request('/');

        $this->assertSame('401 Unauthorized', $response->header('Status'));
    }
}
