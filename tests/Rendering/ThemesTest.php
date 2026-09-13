<?php

declare(strict_types=1);

namespace Ivfi\Tests\Rendering;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;

/**
 * Theme discovery and selection.
 *
 * The default theme path was built as `/` + `/indexer/` + `/themes/`, and the
 * doubled slashes survived into the page. The client compares that value
 * against stylesheet URLs to find the sheet to replace on a theme change, and
 * a value with doubled slashes matched nothing.
 */
final class ThemesTest extends IndexerTestCase
{
    public function testDefaultThemePathHasNoDoubledSlashes(): void
    {
        $fixture = new Fixture('themes-default');

        $response = Indexer::render($fixture);

        $this->assertSame('', $response->stderr);
        $this->assertSame('/indexer/themes/', $response->jsConfig()['style']['themes']['path']);
    }

    public function testConfiguredThemePathIsNormalised(): void
    {
        $fixture = new Fixture('themes-configured');
        $fixture->config(['style' => ['themes' => ['path' => 'indexer//css/themes']]]);

        $response = Indexer::render($fixture);

        $this->assertSame('', $response->stderr);
        $this->assertSame('/indexer/css/themes/', $response->jsConfig()['style']['themes']['path']);
    }

    private function themedFixture(): Fixture
    {
        $fixture = new Fixture('themes-pool');
        $fixture->directory('themes');
        $fixture->file('themes/dark.css', 'body{}');

        /* A theme directory whose name is not a valid regular expression */
        $fixture->directory('themes/a(b');
        $fixture->file('themes/a(b/index.css', 'body{}');

        $fixture->config(['style' => ['themes' => ['path' => '/themes/']]]);

        return $fixture;
    }

    /**
     * A directory name was spliced into a pattern unquoted, so `a(b` was a
     * compilation failure, a warning, and an empty pool.
     */
    public function testThemeDirectoryNamesAreNotTreatedAsPatterns(): void
    {
        $response = Indexer::render($this->themedFixture());

        $this->assertSame('', $response->stderr, 'discovery emitted errors');

        $pool = $response->jsConfig()['style']['themes']['pool'];

        $this->assertSame(['default', 'dark', 'a(b'], array_keys($pool));
        $this->assertSame('/themes/dark.css', $pool['dark']['path']);
        $this->assertSame('/themes/a(b/index.css', $pool['a(b']['path']);
    }

    public function testSelectedThemeIsLinkedFromTheHead(): void
    {
        $response = Indexer::render($this->themedFixture(), '/', [], [
            'IVFi' => '{"style":{"theme":"dark"}}',
        ]);

        $this->assertSame('', $response->stderr);
        $this->assertSame('dark', $response->jsConfig()['style']['themes']['set']);
        $this->assertMatchesRegularExpression(
            '#<link rel="stylesheet" type="text/css" href="/themes/dark\.css\?bust=[a-f0-9]+">#',
            $response->body
        );
    }

    public function testConfiguredDefaultThemeApplies(): void
    {
        $fixture = $this->themedFixture();
        $fixture->config(['style' => ['themes' => ['path' => '/themes/', 'default' => 'Dark']]]);

        $response = Indexer::render($fixture);

        $this->assertSame('', $response->stderr);
        $this->assertSame('dark', $response->jsConfig()['style']['themes']['set']);
        $this->assertStringContainsString('href="/themes/dark.css?bust=', $response->body);
    }
}
