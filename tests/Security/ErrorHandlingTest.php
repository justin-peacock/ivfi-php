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
     * A missing path is a failure of the request, not of the server. The page
     * links a favicon, so a deployment without one asks for a path that is not
     * there on every single view.
     */
    public function testMissingPathAnswersWithNotFound(): void
    {
        if (Indexer::cgiBinary() === null) {
            $this->markTestSkipped('php-cgi is not available');
        }

        $fixture = new Fixture('error-status');

        $response = Indexer::renderCgi($fixture, '/favicon.ico');

        $this->assertSame('404 Not Found', $response->header('Status'));
    }

    /**
     * Following from that, a missing path must not write to the log, or every
     * request for anything absent would add a line to it.
     */
    public function testMissingPathIsNotLogged(): void
    {
        $fixture = new Fixture('error-nolog');

        $response = Indexer::render($fixture, '/favicon.ico');

        $this->assertStringNotContainsString('IVFi:', $response->stderr);
    }

    /**
     * A containment failure is worth knowing about, either as a
     * misconfiguration or as somebody probing, so that one is still recorded.
     */
    public function testContainmentFailureIsLoggedAndForbidden(): void
    {
        $fixture = new Fixture('error-containment');
        $fixture->file('public.txt');

        $response = Indexer::render($fixture, '/../');

        $this->assertStringContainsString('IVFi:', $response->stderr);
        $this->assertStringContainsString('below the public working directory', $response->stderr);
    }

    public function testContainmentFailureAnswersWithForbidden(): void
    {
        if (Indexer::cgiBinary() === null) {
            $this->markTestSkipped('php-cgi is not available');
        }

        $fixture = new Fixture('error-containment-status');
        $fixture->file('public.txt');

        $response = Indexer::renderCgi($fixture, '/../');

        $this->assertSame('403 Forbidden', $response->header('Status'));
    }
}
