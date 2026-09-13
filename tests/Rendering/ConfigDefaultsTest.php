<?php

declare(strict_types=1);

namespace Ivfi\Tests\Rendering;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;
use Ivfi\Tests\Support\Response;

/**
 * The values a config file leaves unset.
 *
 * These were kept as a second copy of the configuration block, and the copy
 * had drifted: a config file that said nothing about dates or the favicon got
 * `m/d/y` and a `.png` where a bare install got `d/m/y` and an `.ico`.
 */
final class ConfigDefaultsTest extends IndexerTestCase
{
    /**
     * Whatever a bare install puts in the head and hands to its script, an
     * install with an empty config file must produce too. The listing itself
     * is left out because the config file is an entry in it.
     */
    public function testEmptyConfigFileRendersLikeNoConfigFile(): void
    {
        $bare = new Fixture('defaults-bare');
        $configured = new Fixture('defaults-configured');
        $configured->config([]);

        $head = static function (Response $response): string {
            preg_match('#<head>(.*?)</head>#s', $response->body, $m);

            return preg_replace('/bust=[a-f0-9]+/', 'bust=X', $m[1] ?? '');
        };

        $settings = static function (Response $response): array {
            $config = $response->jsConfig();
            unset($config['bust'], $config['timestamp']);

            return $config;
        };

        $expected = Indexer::render($bare);
        $actual = Indexer::render($configured);

        $this->assertSame('', $actual->stderr);
        $this->assertSame($head($expected), $head($actual));
        $this->assertSame($settings($expected), $settings($actual));
    }

    public function testDocumentedDefaultsApplyWithAConfigFile(): void
    {
        $fixture = new Fixture('defaults-documented');
        $fixture->config(['debug' => false]);

        $response = Indexer::render($fixture);
        $config = $response->jsConfig();

        $this->assertSame(['d/m/y H:i', 'd/m/y'], $config['format']['date']);
        $this->assertStringContainsString(
            '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">',
            $response->body
        );
    }

    /**
     * A size beyond the last configured unit was indexed past the end of the
     * units array, which is a warning and a number with no unit at all.
     */
    public function testSizesBeyondTheLastUnitUseTheLastUnit(): void
    {
        $fixture = new Fixture('defaults-units');
        $fixture->file('big.bin', str_repeat('a', 2048));
        $fixture->config(['format' => ['sizes' => [' B']]]);

        $response = Indexer::render($fixture);

        $this->assertSame('', $response->stderr, 'formatting emitted errors');
        $this->assertStringContainsString('<td data-raw="2048">2048 B</td>', $response->rows());
    }
}
