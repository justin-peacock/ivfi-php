<?php

declare(strict_types=1);

namespace Ivfi\Tests\Rendering;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;

/**
 * Snapshots of the whole rendered page.
 *
 * The encoding work touched the one function every element passes through, so
 * the risk was never that a hostile name slipped out, it was that an ordinary
 * page changed shape without anyone noticing. These lock the output down so a
 * refactor has to justify any difference.
 *
 * Regenerate with `UPDATE_GOLDEN=1 vendor/bin/phpunit`, and read the diff
 * before committing it.
 */
final class GoldenListingTest extends IndexerTestCase
{
    private const GOLDEN_DIR = __DIR__ . '/golden';

    /**
     * A tree that covers each row type and each column, with names that are
     * ordinary enough to stay readable in the snapshot.
     */
    /**
     * Sorting is off by default, which leaves the order to the filesystem, and
     * that differs between platforms. Turn the script's own sort on for these
     * fixtures so a snapshot describes the renderer rather than the machine.
     *
     * SORT_ASC is written as its value because the config is exported to a
     * file rather than evaluated here.
     */
    private function deterministicOrder(Fixture $fixture): void
    {
        $fixture->config([
            'sorting' => [
                'enabled' => true,
                'sort_by' => 'name',
                'order'   => 4,
                'types'   => 0,
            ],
        ]);
    }

    private function tree(Fixture $fixture): void
    {
        $fixture->directory('albums');
        $fixture->directory('notes dir');

        $fixture->file('photo.jpg', str_repeat('a', 2048));
        $fixture->file('clip.mp4', str_repeat('b', 1048576));
        $fixture->file('empty.txt', '');
        $fixture->file('read me.txt', 'hello');
        $fixture->file('archive.tar.gz', str_repeat('d', 100));
    }

    /**
     * Removes the parts that legitimately move between runs.
     */
    private function normalise(string $html): string
    {
        return preg_replace(
            [
                '/"bust":"[a-f0-9]+"/',
                '/bust=[a-f0-9]+/',
                '/[0-9]+\.[0-9]{6}s/',
                '/"timestamp":[0-9]+/',
                '/data-raw="1[0-9]{9}"/',
            ],
            [
                '"bust":"BUST"',
                'bust=BUST',
                'TIMEs',
                '"timestamp":TIMESTAMP',
                'data-raw="MTIME"',
            ],
            $html
        );
    }

    /**
     * Puts the rows in a fixed order.
     *
     * Listing order depends on what the filesystem hands back, which differs
     * between platforms, so a snapshot that encodes it describes the machine
     * rather than the renderer. Order is worth testing, but as a property
     * rather than a snapshot: see OrderingTest.
     */
    private function orderRows(string $html): string
    {
        preg_match_all('#<tr class="(?:file|directory|parent)">.*?</tr>#s', $html, $m);

        if ($m[0] === []) {
            return $html;
        }

        $rows = $m[0];
        sort($rows, SORT_STRING);

        $index = 0;

        return preg_replace_callback(
            '#<tr class="(?:file|directory|parent)">.*?</tr>#s',
            static function () use ($rows, &$index): string {
                return $rows[$index++];
            },
            $html
        );
    }

    private function assertMatchesGolden(string $name, string $actual): void
    {
        $path = self::GOLDEN_DIR . '/' . $name . '.html';

        if (!is_dir(self::GOLDEN_DIR)) {
            mkdir(self::GOLDEN_DIR, 0755, true);
        }

        if (getenv('UPDATE_GOLDEN') === '1' || !file_exists($path)) {
            file_put_contents($path, $actual);

            if (getenv('UPDATE_GOLDEN') !== '1') {
                $this->fail(sprintf(
                    'No golden file for "%s"; wrote one. Review %s and re-run.',
                    $name,
                    $path
                ));
            }
        }

        $this->assertSame(
            file_get_contents($path),
            $actual,
            sprintf(
                'Rendered output no longer matches %s. If the change is intended, '
                . 'regenerate with UPDATE_GOLDEN=1 and review the diff.',
                $path
            )
        );
    }

    public function testRootListing(): void
    {
        $fixture = new Fixture('golden-root');
        $this->deterministicOrder($fixture);
        $this->tree($fixture);

        $response = Indexer::render($fixture);

        $this->assertSame('', $response->stderr);
        $this->assertMatchesGolden('root-listing', $this->normalise($this->orderRows($response->body)));
    }

    public function testSubdirectoryListing(): void
    {
        $fixture = new Fixture('golden-sub');
        $this->deterministicOrder($fixture);
        $fixture->directory('albums');
        $fixture->file('albums/one.jpg', 'a');
        $fixture->file('albums/two.png', 'bb');

        $response = Indexer::render($fixture, '/albums/');

        $this->assertSame('', $response->stderr);
        $this->assertMatchesGolden('subdirectory-listing', $this->normalise($this->orderRows($response->body)));
    }

    public function testEmptyDirectoryListing(): void
    {
        $fixture = new Fixture('golden-empty');
        $this->deterministicOrder($fixture);
        $fixture->directory('nothing');

        $response = Indexer::render($fixture, '/nothing/');

        $this->assertSame('', $response->stderr);
        $this->assertMatchesGolden('empty-listing', $this->normalise($this->orderRows($response->body)));
    }

    /**
     * The rows for hostile names are snapshotted too, so that a future change
     * to the encoding shows up as a readable diff rather than only as a pass
     * or fail on the injection assertions.
     */
    public function testHostileNamesListing(): void
    {
        $fixture = new Fixture('golden-hostile');
        $this->deterministicOrder($fixture);

        foreach ($this->asciiHostileNames() as $name) {
            $fixture->file($name . '.jpg');
        }

        if ($fixture->skipped() !== []) {
            $this->markTestSkipped(sprintf(
                'the filesystem rejected %d name(s), so the snapshot would not be stable',
                count($fixture->skipped())
            ));
        }

        $response = Indexer::render($fixture);

        $this->assertSame('', $response->stderr);
        $this->assertMatchesGolden('hostile-names-rows', $this->normalise($this->orderRows($response->rows())));
    }
}
