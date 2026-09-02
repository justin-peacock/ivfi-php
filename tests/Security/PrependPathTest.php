<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;

/**
 * `X-Indexer-Prepend-Path` rewrites every link on the page. It was read from
 * the request unconditionally, so any visitor could set it, and its value was
 * emitted unencoded.
 */
final class PrependPathTest extends IndexerTestCase
{
    private const HOSTILE = 'x" onerror="alert(7)" data-z="';

    public function testHeaderIsIgnoredByDefault(): void
    {
        $fixture = new Fixture('prepend-default');
        $fixture->file('image.jpg');

        $response = Indexer::render($fixture, '/', [
            'HTTP_X_INDEXER_PREPEND_PATH' => '/injected/',
        ]);

        $this->assertStringContainsString('href="/image.jpg"', $response->body);
        $this->assertStringNotContainsString('/injected/', $response->body);
    }

    public function testHostileHeaderCannotInjectMarkupWhenIgnored(): void
    {
        $fixture = new Fixture('prepend-hostile');
        $fixture->file('image.jpg');

        $response = Indexer::render($fixture, '/', [
            'HTTP_X_INDEXER_PREPEND_PATH' => self::HOSTILE,
        ]);

        $this->assertNoInjectedMarkup($response, 'the prepend header');
    }

    /**
     * The documented reverse proxy deployment has to keep working once the
     * operator opts in.
     */
    public function testHeaderIsHonouredWhenTrusted(): void
    {
        $fixture = new Fixture('prepend-trusted');
        $fixture->file('image.jpg');
        $fixture->config(['trust_prepend_header' => true]);

        $response = Indexer::render($fixture, '/', [
            'HTTP_X_INDEXER_PREPEND_PATH' => '/file/upstream/',
        ]);

        $this->assertStringContainsString('href="/file/upstream/image.jpg"', $response->body);
    }

    /**
     * Opting in must not also opt into arbitrary text in the markup.
     */
    public function testHostileHeaderIsDiscardedEvenWhenTrusted(): void
    {
        $fixture = new Fixture('prepend-trusted-hostile');
        $fixture->file('image.jpg');
        $fixture->config(['trust_prepend_header' => true]);

        $response = Indexer::render($fixture, '/', [
            'HTTP_X_INDEXER_PREPEND_PATH' => self::HOSTILE,
        ]);

        $this->assertNoInjectedMarkup($response, 'the trusted prepend header');
        $this->assertStringContainsString('href="/image.jpg"', $response->body);
    }

    /**
     * The server variable is set by the operator rather than the visitor, so
     * it stays available without opting in.
     */
    public function testServerVariableIsHonouredWithoutOptIn(): void
    {
        $fixture = new Fixture('prepend-server-var');
        $fixture->file('image.jpg');

        $response = Indexer::render($fixture, '/', [
            'INDEXER_PREPEND_PATH' => '/file/upstream/',
        ]);

        $this->assertStringContainsString('href="/file/upstream/image.jpg"', $response->body);
    }
}
