<?php

declare(strict_types=1);

namespace Ivfi\Tests\Rendering;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;

/**
 * The kind, and so the icon and color, each file is listed with.
 *
 * The golden snapshots only happen to cover a few kinds, so this lists a file
 * of every kind in `FILE_KINDS`, plus the cases around the edges, and checks
 * each row. One render covers the whole table, since every render is a process.
 */
final class FileKindsTest extends IndexerTestCase
{
    /**
     * File name => [expected `data-kind`, or null for none; expected icon]
     *
     * @return array<string, array{0: ?string, 1: string}>
     */
    private function cases(): array
    {
        return [
            /* Media from the `extensions` option, which also previews */
            'photo.jpg'        => ['image', 'file-image'],
            'clip.mp4'         => ['video', 'file-video'],

            /* One of each kind from the extension lists */
            'raw.heic'         => ['image', 'file-image'],
            'movie.mkv'        => ['video', 'file-video'],
            'song.mp3'         => ['audio', 'file-music'],
            'report.pdf'       => ['pdf', 'file-text'],
            'notes.md'         => ['document', 'file-text'],
            'budget.xlsx'      => ['spreadsheet', 'file-spreadsheet'],
            'deck.pptx'        => ['presentation', 'file-chart-column'],
            'bundle.zip'       => ['archive', 'file-archive'],
            'installer.dmg'    => ['package', 'file-box'],
            'main.ts'          => ['code', 'file-code'],
            'config.json'      => ['data', 'file-braces'],
            'Inter.woff2'      => ['font', 'file-type'],
            'server.pem'       => ['key', 'file-key'],

            /* Edges */
            'backup.tar.gz'    => ['archive', 'file-archive'],
            'SHOUTING.PDF'     => ['pdf', 'file-text'],
            'random.bin'       => [null, 'file'],
            'Makefile'         => [null, 'file'],
        ];
    }

    public function testEveryKindGetsItsAttributeAndIcon(): void
    {
        $fixture = new Fixture('file-kinds');

        foreach (array_keys($this->cases()) as $name) {
            $fixture->file($name);
        }

        $rows = Indexer::render($fixture)->rows();

        foreach ($this->cases() as $name => [$kind, $icon]) {
            $found = preg_match(
                sprintf(
                    '#<tr class="file"(?: data-kind="([a-z]+)")?><td data-raw="%s"><a [^>]*><svg [^>]*class="icon icon-([a-z-]+)"#',
                    preg_quote($name, '#')
                ),
                $rows,
                $m
            );

            $this->assertSame(1, $found, "{$name} was not listed");
            $this->assertSame($kind, ($m[1] ?? '') === '' ? null : $m[1], "{$name} has the wrong data-kind");
            $this->assertSame($icon, $m[2], "{$name} has the wrong icon");
        }
    }

    /**
     * Only media from the `extensions` option previews. The kind lists add
     * icons, not previews, for formats a browser cannot show.
     */
    public function testOnlyConfiguredMediaPreviews(): void
    {
        $fixture = new Fixture('file-kinds-preview');
        $fixture->file('photo.jpg');
        $fixture->file('raw.heic');

        $rows = Indexer::render($fixture)->rows();

        $this->assertStringContainsString('<a href="/photo.jpg" class="preview">', $rows);
        $this->assertStringContainsString('<a href="/raw.heic">', $rows);
    }
}
