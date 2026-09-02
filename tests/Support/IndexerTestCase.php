<?php

declare(strict_types=1);

namespace Ivfi\Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class IndexerTestCase extends TestCase
{
    /**
     * Names that have historically broken the renderer, or that would if
     * anything stopped encoding output. Each is a filename the indexer has to
     * survive listing.
     *
     * @return array<string, string>
     */
    protected function hostileNames(): array
    {
        return [
            'attribute breakout'  => 'pwn" onmouseover="alert(1)',
            'tag breakout'        => '<img src=x onerror=alert(1)>',
            'both at once'        => 'a" onmouseover="alert(1) <img src=x onerror=alert(2)>.jpg',
            'script element'      => '<script>alert(1)',
            'single quotes'       => "it's a 'quoted' name.png",
            'ampersand'           => 'a&b&amp;c.txt',
            'already encoded'     => '&lt;script&gt;.txt',
            'angle brackets'      => '<>.txt',
            'unicode'             => 'café-naïve-日本語.png',
            'rtl override'        => "photo\u{202E}gnp.exe",
            'percent encoding'    => 'a%22b%3Cc.txt',
            'backslash'           => 'back\\slash.txt',
            'hash and question'   => 'a#b?c.txt',
            'leading dash'        => '-dashed.txt',
            'many dots'           => 'a...b..c.txt',
        ];
    }

    /**
     * The subset of the above that is safe to snapshot.
     *
     * Filenames are bytes, and the same name is not the same bytes everywhere:
     * macOS stores them decomposed while Linux keeps whatever it was given, so
     * `café` differs between the two both in the rendered output and in where
     * it sorts. Golden files therefore stay ASCII, and the non-ASCII cases are
     * covered by the assertion based tests, which care about the encoding
     * rather than the exact bytes.
     *
     * @return array<string, string>
     */
    protected function asciiHostileNames(): array
    {
        return array_filter(
            $this->hostileNames(),
            static fn (string $name): bool => preg_match('/^[\x20-\x7E]*$/', $name) === 1
        );
    }

    /**
     * Asserts that nothing in the document became markup that the script did
     * not intend to emit.
     *
     * This parses the result rather than searching it for strings. An encoded
     * name legitimately contains text such as `onerror=alert(1)` inside an
     * attribute value, and a substring search cannot tell that apart from a
     * real event handler; the parsed tree can.
     */
    protected function assertNoInjectedMarkup(Response $response, string $context = ''): void
    {
        $context = $context === '' ? 'the document' : $context;

        $dom = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $response->body,
            LIBXML_NOWARNING | LIBXML_NOERROR
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);

        /* The indexer never emits an image, so any element here was injected */
        $this->assertSame(
            0,
            $xpath->query('//img')->length,
            $context . ' introduced an <img> element'
        );

        /* No element may carry an event handler */
        foreach ($xpath->query('//@*') as $attribute) {
            $this->assertDoesNotMatchRegularExpression(
                '/^on/i',
                $attribute->nodeName,
                sprintf('%s introduced the event handler %s', $context, $attribute->nodeName)
            );
        }

        /* No link or source may run script */
        foreach ($xpath->query('//@href | //@src') as $attribute) {
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*javascript:/i',
                $attribute->nodeValue ?? '',
                $context . ' introduced a javascript: URL'
            );
        }

        /* Only the script elements the page is built with may be present */
        foreach ($xpath->query('//script') as $script) {
            $id  = $script->attributes?->getNamedItem('id')?->nodeValue ?? '';
            $src = $script->attributes?->getNamedItem('src')?->nodeValue ?? '';

            $expected = $id === '__IVFI_DATA__'
                || str_contains($src, 'main.js')
                || str_contains($script->textContent, 'getScrollbarWidth');

            $this->assertTrue(
                $expected,
                sprintf(
                    '%s introduced an unexpected <script>: %s',
                    $context,
                    substr(trim($script->textContent), 0, 80)
                )
            );
        }
    }
}
