<?php

declare(strict_types=1);

namespace Ivfi\Tests\Support;

/**
 * Renders the built indexer the way a web server would.
 *
 * The script runs top to bottom and writes its page to stdout, so the cheapest
 * faithful way to exercise it is to run it in its own process with $_SERVER
 * prepared. That keeps every test against the artifact that actually ships,
 * rather than against pieces lifted out of it.
 */
final class Indexer
{
    /**
     * @param array<string, string> $server Extra $_SERVER values
     */
    public static function render(
        Fixture $fixture,
        string $uri = '/',
        array $server = []
    ): Response {
        $server = array_merge([
            'REQUEST_URI'     => $uri,
            'REQUEST_METHOD'  => 'GET',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME'     => 'indexer.test',
        ], $server);

        /**
         * Kept outside the fixture, or the runner would show up as an entry in
         * the very listing under test. BASE_PATH is derived from the location
         * of indexer.php, so it does not care where this file lives.
         */
        $runner = sprintf(
            '%s/ivfi-run-%s.php', sys_get_temp_dir(), bin2hex(random_bytes(6))
        );

        $written = file_put_contents($runner, sprintf(
            "<?php\n\$_SERVER = array_merge(\$_SERVER, %s);\nrequire %s;\n",
            var_export($server, true),
            var_export($fixture->root() . '/indexer.php', true)
        ));

        if ($written === false) {
            throw new \RuntimeException("Could not write the runner to {$runner}");
        }

        /* finally, so a failure to start does not leave the runner behind */
        try {
            $process = proc_open(
                [PHP_BINARY, '-d', 'display_errors=0', '-d', 'error_reporting=E_ALL', $runner],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                $fixture->root()
            );

            if (!is_resource($process)) {
                throw new \RuntimeException('Could not start the indexer process');
            }

            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            $status = proc_close($process);
        } finally {
            @unlink($runner);
        }

        return new Response($stdout, $stderr, $status);
    }

}


/**
 * The output of a single render.
 */
final class Response
{
    public readonly string $body;

    /** @var array<string, string> */
    public readonly array $headers;

    /** @var list<string> Header lines as sent, so repeats are not lost */
    public readonly array $rawHeaderLines;

    public function __construct(
        string $output,
        public readonly string $stderr,
        public readonly int $status,
        bool $hasHeaders = false
    ) {
        if (!$hasHeaders) {
            $this->body = $output;
            $this->headers = [];
            $this->rawHeaderLines = [];

            return;
        }

        /* A CGI response is headers, a blank line, then the body */
        $split = preg_split("/\r?\n\r?\n/", $output, 2);
        $headers = [];

        foreach (preg_split("/\r?\n/", $split[0] ?? '') ?: [] as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        $this->body = $split[1] ?? '';
        $this->headers = $headers;
        $this->rawHeaderLines = preg_split("/\r?\n/", $split[0] ?? '') ?: [];
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Cookies the response set, by name.
     *
     * Parsed from the raw header rather than the folded map, because a
     * response can legitimately carry more than one Set-Cookie.
     *
     * @return array<string, string>
     */
    public function setCookies(): array
    {
        $cookies = [];

        foreach ($this->rawHeaderLines as $line) {
            if (stripos($line, 'set-cookie:') !== 0) {
                continue;
            }

            $pair = trim(explode(';', substr($line, 11), 2)[0]);

            if (str_contains($pair, '=')) {
                [$name, $value] = explode('=', $pair, 2);
                $cookies[trim($name)] = trim($value);
            }
        }

        return $cookies;
    }

    /**
     * The attributes on a named Set-Cookie, lowercased.
     *
     * @return list<string>
     */
    public function cookieAttributes(string $name): array
    {
        foreach ($this->rawHeaderLines as $line) {
            if (stripos($line, 'set-cookie:') !== 0) {
                continue;
            }

            $parts = explode(';', substr($line, 11));

            if (strpos(trim($parts[0]), $name . '=') === 0) {
                return array_map(
                    static fn (string $p): string => strtolower(trim($p)),
                    array_slice($parts, 1)
                );
            }
        }

        return [];
    }

    /**
     * The rendered rows only, so assertions are not distracted by the shell
     * of the document.
     */
    public function rows(): string
    {
        preg_match_all('#<tr class="(?:file|directory|parent)">.*?</tr>#s', $this->body, $m);

        return implode("\n", $m[0]);
    }

    public function title(): string
    {
        preg_match('#<title>(.*?)</title>#s', $this->body, $m);

        return $m[1] ?? '';
    }

    /**
     * The breadcrumb element that sits above the listing.
     */
    public function breadcrumb(): string
    {
        preg_match('#<div class="path">(.*?)</div>#s', $this->body, $m);

        return $m[1] ?? '';
    }

    /**
     * Every attribute value the document contains.
     *
     * @return list<string>
     */
    public function attributeValues(): array
    {
        preg_match_all('#\s[A-Za-z_:][A-Za-z0-9_:.\-]*="([^"]*)"#', $this->body, $m);

        return $m[1];
    }
}
