<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;

/**
 * The top level handler echoed the exception, which stringifies to include the
 * stack trace and absolute filesystem paths, and it was not gated on the debug
 * setting. Requesting a path that did not exist was enough to read it.
 */
final class ErrorHandlingTest extends IndexerTestCase
{
    public function testMissingPathDoesNotLeakTraceOrPaths(): void
    {
        $fixture = new Fixture('error-missing');

        $response = Indexer::render($fixture, '/does-not-exist/');

        $this->assertStringNotContainsString('Stack trace', $response->body);
        $this->assertStringNotContainsString($fixture->root(), $response->body);
        $this->assertStringNotContainsString('indexer.php:', $response->body);
        $this->assertStringNotContainsString('Indexer->__construct', $response->body);
    }

    /**
     * A hostile path that does not exist must not be reflected into the error
     * page either.
     */
    public function testMissingPathIsNotReflectedIntoTheErrorPage(): void
    {
        $fixture = new Fixture('error-reflect');

        $response = Indexer::render($fixture, '/nope<script>alert(3)</script>/');

        $this->assertNoInjectedMarkup($response, 'a hostile missing path');
    }

    /**
     * The detail is still available where it is wanted.
     */
    public function testDebugModeStillShowsDetail(): void
    {
        $fixture = new Fixture('error-debug');
        $fixture->config(['debug' => true]);

        $response = Indexer::render($fixture, '/does-not-exist/');

        $this->assertStringContainsString('Stack trace', $response->body);
    }

    /**
     * Whatever is shown, the exception should always reach the server log.
     */
    public function testExceptionIsLogged(): void
    {
        $fixture = new Fixture('error-log');

        $response = Indexer::render($fixture, '/does-not-exist/', [
            /* error_log() with no destination writes to stderr under the CLI */
        ]);

        $this->assertStringContainsString('IVFi:', $response->stderr);
        $this->assertStringContainsString('path does not exist', $response->stderr);
    }
}
