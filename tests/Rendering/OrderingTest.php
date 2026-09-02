<?php

declare(strict_types=1);

namespace Ivfi\Tests\Rendering;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;

/**
 * Listing order, asserted as a property.
 *
 * With sorting off the order is whatever the filesystem hands back, which is
 * not the same everywhere, so this is deliberately not covered by a snapshot.
 * What the script promises is that when sorting is enabled it decides the
 * order itself, and that is what these check.
 */
final class OrderingTest extends IndexerTestCase
{
    /** SORT_ASC and SORT_DESC, written as values because the config is exported */
    private const ASC  = 4;
    private const DESC = 3;

    private function sorted(Fixture $fixture, string $by, int $order = self::ASC): void
    {
        $fixture->config([
            'sorting' => [
                'enabled' => true,
                'sort_by' => $by,
                'order'   => $order,
                'types'   => 0,
            ],
        ]);
    }

    /**
     * Names by row type. Directories and files are ordered as separate groups,
     * with the directories first, so they are compared separately too.
     *
     * @return list<string>
     */
    private function names(string $rows, string $type): array
    {
        preg_match_all(
            sprintf('#<tr class="%s"><td data-raw="([^"]*)"><a #', preg_quote($type, '#')),
            $rows,
            $m
        );

        return array_map(
            static fn (string $v): string => html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $m[1]
        );
    }

    private function assertOrdered(array $names, bool $ascending, string $what): void
    {
        $this->assertNotEmpty($names, "no {$what} were listed");

        $expected = $names;
        $ascending ? sort($expected, SORT_STRING) : rsort($expected, SORT_STRING);

        $this->assertSame($expected, $names, "{$what} were not in the expected order");
    }

    private function tree(Fixture $fixture): void
    {
        $fixture->directory('zebra');
        $fixture->directory('alpha');
        $fixture->file('mike.txt', 'aaa');
        $fixture->file('bravo.txt', 'a');
        $fixture->file('yankee.txt', 'aa');
    }

    public function testNamesAscendWhenSortingByName(): void
    {
        $fixture = new Fixture('order-name');
        $this->sorted($fixture, 'name');
        $this->tree($fixture);

        $rows = Indexer::render($fixture)->rows();

        $this->assertOrdered($this->names($rows, 'directory'), true, 'directories');
        $this->assertOrdered($this->names($rows, 'file'), true, 'files');

        /* Directories are listed as a group before the files */
        $this->assertLessThan(
            strpos($rows, 'bravo.txt'),
            strpos($rows, 'zebra'),
            'a directory was listed after a file'
        );
    }

    public function testOrderReversesWhenDescending(): void
    {
        $fixture = new Fixture('order-desc');
        $this->sorted($fixture, 'name', self::DESC);
        $this->tree($fixture);

        $rows = Indexer::render($fixture)->rows();

        $this->assertOrdered($this->names($rows, 'directory'), false, 'directories');
        $this->assertOrdered($this->names($rows, 'file'), false, 'files');
    }

    /**
     * The order must come from the script, not from the order the entries
     * happen to have been created in.
     */
    public function testOrderDoesNotDependOnCreationOrder(): void
    {
        $forward = new Fixture('order-forward');
        $this->sorted($forward, 'name');
        $forward->file('alpha.txt', 'a');
        $forward->file('bravo.txt', 'a');
        $forward->file('charlie.txt', 'a');

        $reverse = new Fixture('order-reverse');
        $this->sorted($reverse, 'name');
        $reverse->file('charlie.txt', 'a');
        $reverse->file('bravo.txt', 'a');
        $reverse->file('alpha.txt', 'a');

        $this->assertSame(
            $this->names(Indexer::render($forward)->rows(), 'file'),
            $this->names(Indexer::render($reverse)->rows(), 'file'),
            'the listing order followed the order the files were created in'
        );
    }

    public function testSortingBySizeOrdersBySize(): void
    {
        $fixture = new Fixture('order-size');
        $this->sorted($fixture, 'size');
        $fixture->file('big.txt', str_repeat('a', 3000));
        $fixture->file('small.txt', 'a');
        $fixture->file('medium.txt', str_repeat('a', 100));

        $rows = Indexer::render($fixture)->rows();

        $this->assertLessThan(
            strpos($rows, 'big.txt'),
            strpos($rows, 'small.txt'),
            'the smallest file did not come first'
        );
    }
}
