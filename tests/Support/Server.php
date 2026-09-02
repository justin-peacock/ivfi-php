<?php

declare(strict_types=1);

namespace Ivfi\Tests\Support;

/**
 * Serves a fixture through PHP's built-in web server.
 *
 * Most tests can render the script in a plain subprocess, but anything that
 * needs the response status or headers cannot: the CLI discards them. A CGI
 * binary would give them, but `php-cgi` is not installable on the CI runners
 * for most PHP versions, so the built-in server is used instead. It reports
 * status and headers, and it ships with PHP itself.
 */
final class Server
{
    /** @var resource|null */
    private $process = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    private int $port;

    private string $router;

    /** @var array<string, string> */
    private array $cookies = [];

    public function __construct(private Fixture $fixture)
    {
        $this->port = self::freePort();
        $this->router = sprintf(
            '%s/ivfi-router-%s.php', sys_get_temp_dir(), bin2hex(random_bytes(6))
        );

        /**
         * The built-in server hands the credentials over as a plain header,
         * where the script expects the value a CGI SAPI would have put in
         * PHP_AUTH_DIGEST, so bridge the two. Kept outside the document root
         * so the router does not appear in the listing it is serving.
         */
        $written = file_put_contents($this->router, <<<'PHP'
<?php
if (isset($_SERVER['HTTP_AUTHORIZATION'])
    && stripos($_SERVER['HTTP_AUTHORIZATION'], 'Digest ') === 0) {
    $_SERVER['PHP_AUTH_DIGEST'] = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
}

$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = $root . rawurldecode((string) $path);

/* Let the server deliver real static files itself */
if ($path !== '/' && is_file($file) && substr($file, -4) !== '.php') {
    return false;
}

require $root . '/indexer.php';
PHP);

        if ($written === false) {
            throw new \RuntimeException(
                "Could not write the router to {$this->router}"
            );
        }

        $this->process = proc_open(
            [
                PHP_BINARY,
                '-d', 'display_errors=0',
                '-S', '127.0.0.1:' . $this->port,
                '-t', $this->fixture->root(),
                $this->router,
            ],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $this->pipes,
            $this->fixture->root()
        );

        if (!is_resource($this->process)) {
            /* The destructor never runs for an object whose constructor threw */
            @unlink($this->router);

            throw new \RuntimeException('Could not start the built-in server');
        }

        try {
            $this->waitUntilReady();
        } catch (\RuntimeException $e) {
            $this->stop();

            throw $e;
        }
    }

    /**
     * @param array<string, string> $headers
     */
    /**
     * @param array<string, string> $headers
     * @param array<string, string>|null $post Form fields, which make it a POST
     */
    public function request(
        string $uri,
        array $headers = [],
        ?array $post = null
    ): Response {
        $socket = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 5);

        if ($socket === false) {
            throw new \RuntimeException("Could not connect to the server: {$errstr}");
        }

        $body = $post === null ? '' : http_build_query($post);

        $request = sprintf(
            "%s %s HTTP/1.0\r\nHost: 127.0.0.1:%d\r\n",
            $post === null ? 'GET' : 'POST',
            $uri,
            $this->port
        );

        if ($post !== null) {
            $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $request .= sprintf("Content-Length: %d\r\n", strlen($body));
        }

        /* Anything the jar is holding, unless the caller set its own */
        if ($this->cookies !== [] && !isset($headers['Cookie'])) {
            $pairs = [];

            foreach ($this->cookies as $name => $value) {
                $pairs[] = $name . '=' . $value;
            }

            $request .= 'Cookie: ' . implode('; ', $pairs) . "\r\n";
        }

        foreach ($headers as $name => $value) {
            $request .= sprintf("%s: %s\r\n", $name, $value);
        }

        fwrite($socket, $request . "\r\n" . $body);

        $raw = '';

        while (!feof($socket)) {
            $raw .= (string) fread($socket, 8192);
        }

        fclose($socket);

        /* Drop the status line into a header the Response can read back */
        $raw = preg_replace(
            '#^HTTP/1\.[01] (\d{3} [^\r\n]*)#', 'Status: $1', $raw, 1
        );

        $response = new Response((string) $raw, '', 0, true);

        /* Keep the session across calls, the way a browser would */
        foreach ($response->setCookies() as $name => $value) {
            if ($value === '' || $value === 'deleted') {
                unset($this->cookies[$name]);

                continue;
            }

            $this->cookies[$name] = $value;
        }

        return $response;
    }

    /**
     * Forgets the stored cookies, standing in for a fresh browser.
     */
    public function clearCookies(): void
    {
        $this->cookies = [];
    }

    private function waitUntilReady(): void
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $socket = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 1);

            if ($socket !== false) {
                fclose($socket);

                return;
            }

            usleep(50000);
        }

        throw new \RuntimeException('The built-in server never became ready');
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

        if ($socket === false) {
            throw new \RuntimeException("Could not reserve a port: {$errstr}");
        }

        $name = (string) stream_socket_get_name($socket, false);

        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    public function stop(): void
    {
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        $this->pipes = [];

        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }

        @unlink($this->router);
    }

    public function __destruct()
    {
        $this->stop();
    }
}
