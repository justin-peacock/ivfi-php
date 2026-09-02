<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;

/**
 * Digest authentication compared the response with a loose, non constant time
 * `!=`, and issued nonces from `uniqid()` that were never checked when the
 * client sent them back.
 *
 * These run through CGI so the real challenge can be read from the response
 * headers, which means the exchange uses the server's own nonce rather than
 * one the test reconstructed.
 */
final class AuthenticationTest extends IndexerTestCase
{
    private const USER  = 'alice';
    private const PASS  = 's3cret';
    private const REALM = 'Restricted content.';

    protected function setUp(): void
    {
        if (Indexer::cgiBinary() !== null) {
            return;
        }

        /**
         * Skipping locally is a convenience, but a skip in CI would quietly
         * drop the authentication coverage, so make it an error there.
         */
        if (getenv('IVFI_REQUIRE_CGI') === '1') {
            $this->fail(
                'php-cgi is required when IVFI_REQUIRE_CGI=1 but was not found'
            );
        }

        $this->markTestSkipped('php-cgi is not available');
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
    private function challenge(Fixture $fixture): string
    {
        $response = Indexer::renderCgi($fixture);

        $header = (string) $response->header('WWW-Authenticate');

        $this->assertMatchesRegularExpression('/^Digest /', $header, 'no digest challenge issued');
        $this->assertSame('401 Unauthorized', $response->header('Status'));

        preg_match('/nonce="([^"]+)"/', $header, $m);

        $this->assertNotEmpty($m[1] ?? '', 'challenge carried no nonce');

        return $m[1];
    }

    private function authorize(
        Fixture $fixture,
        string $nonce,
        string $password,
        string $user = self::USER,
        string $uri = '/'
    ): \Ivfi\Tests\Support\Response {
        $cnonce = 'testcnonce';
        $nc     = '00000001';

        $a1 = md5($user . ':' . self::REALM . ':' . $password);
        $a2 = md5('GET:' . $uri);

        $response = md5("{$a1}:{$nonce}:{$nc}:{$cnonce}:auth:{$a2}");

        return Indexer::renderCgi($fixture, $uri, [
            'PHP_AUTH_DIGEST' => sprintf(
                'username="%s", realm="%s", nonce="%s", uri="%s", qop=auth, nc=%s, cnonce="%s", response="%s"',
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
        $fixture = $this->fixture();

        $this->challenge($fixture);
    }

    public function testCorrectPasswordIsAccepted(): void
    {
        $fixture = $this->fixture();

        $result = $this->authorize($fixture, $this->challenge($fixture), self::PASS);

        $this->assertStringContainsString('secret.jpg', $result->body);
        $this->assertStringNotContainsString('Invalid credentials', $result->body);
    }

    public function testWrongPasswordIsRejected(): void
    {
        $fixture = $this->fixture();

        $result = $this->authorize($fixture, $this->challenge($fixture), 'wrong');

        $this->assertStringContainsString('Invalid credentials', $result->body);
        $this->assertStringNotContainsString('secret.jpg', $result->body);
    }

    public function testUnknownUserIsRejected(): void
    {
        $fixture = $this->fixture();

        $result = $this->authorize(
            $fixture, $this->challenge($fixture), self::PASS, 'mallory'
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

        $fixture = $this->fixture('md5:' . $ha1);

        $result = $this->authorize($fixture, $this->challenge($fixture), self::PASS);

        $this->assertStringContainsString('secret.jpg', $result->body);
    }

    public function testPrecomputedHa1CredentialStillRejectsWrongPassword(): void
    {
        $ha1 = md5(self::USER . ':' . self::REALM . ':' . self::PASS);

        $fixture = $this->fixture('md5:' . $ha1);

        $result = $this->authorize($fixture, $this->challenge($fixture), 'wrong');

        $this->assertStringContainsString('Invalid credentials', $result->body);
    }

    /**
     * The nonce used to be `uniqid()` and was never validated, so a client
     * could invent one. It must now be a challenge the server actually issued.
     */
    public function testInventedNonceIsRejected(): void
    {
        $fixture = $this->fixture();

        $result = $this->authorize($fixture, uniqid(), self::PASS);

        $this->assertStringContainsString('Invalid credentials', $result->body);
        $this->assertStringNotContainsString('secret.jpg', $result->body);
    }

    public function testForgedNonceWithPlausibleShapeIsRejected(): void
    {
        $fixture = $this->fixture();

        $forged = time() . ':' . bin2hex(random_bytes(8)) . ':' . str_repeat('a', 64);

        $result = $this->authorize($fixture, $forged, self::PASS);

        $this->assertStringContainsString('Invalid credentials', $result->body);
    }

    /**
     * Each challenge is distinct, so a captured one cannot simply be reused
     * forever.
     */
    public function testEachChallengeIssuesADistinctNonce(): void
    {
        $fixture = $this->fixture();

        $this->assertNotSame(
            $this->challenge($fixture),
            $this->challenge($fixture),
            'the same nonce was issued twice'
        );
    }

    /**
     * The status line was written without a space, producing the malformed
     * `HTTP/1.1401 Unauthorized`.
     */
    public function testChallengeSendsAWellFormedStatus(): void
    {
        $fixture = $this->fixture();

        $response = Indexer::renderCgi($fixture);

        $this->assertSame('401 Unauthorized', $response->header('Status'));
    }
}
