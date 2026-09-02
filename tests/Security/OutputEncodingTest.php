<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;

/**
 * The renderer emitted every value unencoded, so a filename or directory name
 * could close an attribute or a tag. These cover the three vectors that were
 * reproducible against a stock build, and the general property they violated.
 */
final class OutputEncodingTest extends IndexerTestCase
{
    public function testHostileFilenamesCannotInjectMarkup(): void
    {
        $fixture = new Fixture('filenames');
        $created = [];

        foreach ($this->hostileNames() as $label => $name) {
            if ($fixture->file($name . '.jpg')) {
                $created[$label] = $name;
            }
        }

        $this->assertNotEmpty($created, 'the filesystem rejected every hostile name');

        $response = Indexer::render($fixture);

        $this->assertSame('', $response->stderr, 'rendering emitted errors');
        $this->assertNoInjectedMarkup($response, 'a hostile filename');

        /* The names must still be listed, not silently dropped */
        foreach ($created as $label => $name) {
            $this->assertStringContainsString(
                htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'),
                $response->body,
                sprintf('the %s case was not listed at all', $label)
            );
        }
    }

    public function testHostileDirectoryNameCannotInjectMarkup(): void
    {
        $fixture = new Fixture('dirnames');

        foreach ($this->hostileNames() as $name) {
            $fixture->directory($name);
        }

        $response = Indexer::render($fixture);

        $this->assertSame('', $response->stderr);
        $this->assertNoInjectedMarkup($response, 'a hostile directory name');
    }

    /**
     * The directory name reaches the breadcrumb and the page title as well as
     * the listing, which is a separate code path from the rows.
     */
    public function testHostileDirectoryNameIsEncodedInTitleAndBreadcrumb(): void
    {
        $name = 'dir"><script>alert(9)</script>';

        $fixture = new Fixture('breadcrumb');

        if (!$fixture->directory($name)) {
            $this->markTestSkipped('the filesystem rejected the directory name');
        }

        $response = Indexer::render($fixture, '/' . $name . '/');

        $this->assertSame('', $response->stderr);
        $this->assertNoInjectedMarkup($response, 'a hostile directory name in the path');

        $this->assertStringNotContainsString('<script>', $response->title());
        $this->assertStringNotContainsString('<script>', $response->breadcrumb());

        /* The name should still be readable once decoded */
        $this->assertStringContainsString(
            'alert(9)',
            html_entity_decode($response->title(), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
    }

    /**
     * The general property: no attribute value may contain a bare quote or
     * angle bracket, whatever the name on disk was.
     */
    public function testNoAttributeValueContainsUnencodedDelimiters(): void
    {
        $fixture = new Fixture('attributes');

        foreach ($this->hostileNames() as $name) {
            $fixture->file($name . '.jpg');
            $fixture->directory('d ' . $name);
        }

        $response = Indexer::render($fixture);

        foreach ($response->attributeValues() as $value) {
            $this->assertStringNotContainsString('<', $value, 'attribute value holds a raw <');
            $this->assertStringNotContainsString('>', $value, 'attribute value holds a raw >');
        }
    }

    /**
     * A name that already looks encoded must not be decoded on the way out,
     * which would let `&lt;script&gt;` become a live element.
     */
    public function testPreEncodedNameIsNotUnescaped(): void
    {
        $fixture = new Fixture('double-encoding');

        if (!$fixture->file('&lt;script&gt;alert(1)&lt;.txt')) {
            $this->markTestSkipped('the filesystem rejected the name');
        }

        $response = Indexer::render($fixture);

        $this->assertStringNotContainsString('<script>', $response->body);
        $this->assertStringContainsString('&amp;lt;script&amp;gt;', $response->body);
    }
}
