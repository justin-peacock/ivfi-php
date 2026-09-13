<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The `IVFi` cookie is JSON the client writes, and the script reads values
 * out of it with no more than an `isset()`. A theme that was an array rather
 * than a string reached `strtolower()` and took the whole page down with a
 * fatal type error, so any visitor could turn their own view into a 500.
 */
final class ClientCookieTest extends IndexerTestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function malformedCookies(): array
    {
        return [
            'theme is an array'      => ['{"style":{"theme":["dark"]}}'],
            'theme is an object'     => ['{"style":{"theme":{"name":"dark"}}}'],
            'theme is a number'      => ['{"style":{"theme":1}}'],
            'compact is an array'    => ['{"style":{"compact":["x"]}}'],
            'style is a string'      => ['{"style":"dark"}'],
            'sort row is an array'   => ['{"sort":{"row":[1],"ascending":[0]}}'],
            'sort row is an object'  => ['{"sort":{"row":{"a":1}}}'],
            'offset is text'         => ['{"timezoneOffset":"soon"}'],
            'offset is an array'     => ['{"timezoneOffset":[60]}'],
            'top level is a string'  => ['"just text"'],
            'top level is a number'  => ['42'],
            'top level is a list'    => ['[1,2,3]'],
            'not json at all'        => ['{style:dark'],
            'empty'                  => [''],
            'deeply nested'          => [str_repeat('{"a":', 600) . '1' . str_repeat('}', 600)],
        ];
    }

    #[DataProvider('malformedCookies')]
    public function testMalformedCookieStillRendersTheListing(string $cookie): void
    {
        $fixture = new Fixture('cookie');
        $fixture->file('photo.jpg');

        $response = Indexer::render($fixture, '/', [], ['IVFi' => $cookie]);

        $this->assertSame(0, $response->status, 'the script did not exit cleanly');
        $this->assertSame('', $response->stderr, 'rendering emitted errors');
        $this->assertStringContainsString('photo.jpg', $response->body);
        $this->assertNotEmpty($response->jsConfig(), 'the page carried no configuration');
    }

    /**
     * A theme name from the cookie is only used when it names a theme that
     * exists, so a stale or invented one cannot reach the document.
     */
    public function testUnknownThemeInCookieIsNotApplied(): void
    {
        $fixture = new Fixture('cookie-theme');
        $fixture->file('photo.jpg');

        $response = Indexer::render($fixture, '/', [], [
            'IVFi' => '{"style":{"theme":"../../../etc/passwd"}}',
        ]);

        $this->assertSame('', $response->stderr);
        $this->assertSame('default', $response->jsConfig()['style']['themes']['set']);
        $this->assertStringNotContainsString('passwd', $response->body);
    }

    /**
     * Compact mode is a flag, whatever shape the cookie gives it.
     */
    public function testCompactFromCookieIsTreatedAsAFlag(): void
    {
        $fixture = new Fixture('cookie-compact');

        $response = Indexer::render($fixture, '/', [], [
            'IVFi' => '{"style":{"compact":"yes"}}',
        ]);

        $this->assertSame('', $response->stderr);
        $this->assertMatchesRegularExpression('/<body class="[^"]*\bcompact\b/', $response->body);
        $this->assertTrue($response->jsConfig()['style']['compact']);
    }
}
