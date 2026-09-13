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

    /**
     * @param array<string, string> $ini Extra php.ini settings for the server
     */
    public function __construct(private Fixture $fixture, private array $ini = [])
    {
        $this->port = self::freePort();
        $this->router = sprintf(
            '%s/ivfi-router-%s.php', sys_get_temp_dir(), bin2hex(random_bytes(6))
        );

        /**
         * Kept outside the document root so the router does not appear in
         * the listing it is serving.
         */
        $written = file_put_contents($this->router, <<<'PHP'
<?php
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

        $arguments = [PHP_BINARY, '-d', 'display_errors=0'];

        foreach ($this->ini as $name => $value) {
            $arguments[] = '-d';
            $arguments[] = $name . '=' . $value;
        }

        $this->process = proc_open(
            [
                ...$arguments,
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
     * @param array<string, string>|null $post Form fields, which make it a POST
     * @param array<string, array{name: string, contents: string, type?: string}>|null $files
     *        Uploads, keyed by field name, which make the body multipart
     */
    public function request(
        string $uri,
        array $headers = [],
        ?array $post = null,
        ?array $files = null
    ): Response {
        $socket = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 5);

        if ($socket === false) {
            throw new \RuntimeException("Could not connect to the server: {$errstr}");
        }

        if ($files !== null) {
            $boundary = '----ivfi' . bin2hex(random_bytes(8));
            $body = self::multipart($boundary, $post ?? [], $files);
            $contentType = 'multipart/form-data; boundary=' . $boundary;
        } else {
            $body = $post === null ? '' : http_build_query($post);
            $contentType = 'application/x-www-form-urlencoded';
        }

        $isPost = $post !== null || $files !== null;

        $request = sprintf(
            "%s %s HTTP/1.0\r\nHost: 127.0.0.1:%d\r\n",
            $isPost ? 'POST' : 'GET',
            $uri,
            $this->port
        );

        if ($isPost) {
            $request .= sprintf("Content-Type: %s\r\n", $contentType);
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
     * Builds a multipart body the way a browser would.
     *
     * Written out by hand rather than through a client library, because what
     * these tests are checking is how the script reacts to a filename, and a
     * library that sanitises the name on the way out would be testing itself.
     *
     * @param array<string, string> $fields
     * @param array<string, array{name: string, contents: string, type?: string}> $files
     */
    private static function multipart(string $boundary, array $fields, array $files): string
    {
        $body = '';

        foreach ($fields as $name => $value) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
            $body .= $value . "\r\n";
        }

        foreach ($files as $name => $file) {
            $body .= "--{$boundary}\r\n";
            $body .= sprintf(
                "Content-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n",
                $name,
                $file['name']
            );
            $body .= sprintf(
                "Content-Type: %s\r\n\r\n", $file['type'] ?? 'application/octet-stream'
            );
            $body .= $file['contents'] . "\r\n";
        }

        return $body . "--{$boundary}--\r\n";
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
