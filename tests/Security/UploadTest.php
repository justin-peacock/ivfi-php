<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\IndexerTestCase;
use Ivfi\Tests\Support\Response;
use Ivfi\Tests\Support\Server;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Drag and drop uploads.
 *
 * The script writes into a directory the web server is already serving, so the
 * interesting cases are not the ones where a file arrives. They are the ones
 * where something the server would later execute is turned away, and where a
 * request that has not signed in finds no endpoint at all.
 */
final class UploadTest extends IndexerTestCase
{
    private const USER = 'emy';
    private const PASS = 'correct horse battery staple';

    /** @var list<Server> */
    private array $servers = [];

    /** @var array<int, Fixture> Held so the tree outlives the test body */
    private array $fixtures = [];

    protected function tearDown(): void
    {
        foreach ($this->servers as $server) {
            $server->stop();
        }

        $this->servers = [];
        $this->fixtures = [];
    }

    /**
     * A fixture with uploads configured, and authentication unless it is
     * explicitly turned off.
     *
     * @param array<string, mixed> $upload
     * @param array<string, mixed> $extra Anything else to merge into the config
     */
    private function serve(array $upload = [], array $extra = [], bool $auth = true): Server
    {
        $fixture = new Fixture('upload');
        $fixture->directory('incoming');
        $fixture->file('existing.jpg', 'original');

        $config = array_merge([
            'upload' => array_merge(['enabled' => true], $upload),
        ], $extra);

        if ($auth) {
            $config['authentication'] = array_merge([
                'users' => [
                    self::USER => password_hash(self::PASS, PASSWORD_DEFAULT),
                ],
                'throttle_path' => $fixture->root(),
            ], $extra['authentication'] ?? []);
        }

        $fixture->config($config);

        $server = new Server($fixture);

        $this->servers[] = $server;
        $this->fixtures[] = $fixture;

        return $server;
    }

    private function root(): string
    {
        return end($this->fixtures)->root();
    }

    /**
     * Signs in, returning the token the page carries for uploads.
     */
    private function signIn(Server $server, string $uri = '/'): string
    {
        $login = $server->request($uri);

        preg_match('#name="ivfi_csrf" value="([^"]+)"#', $login->body, $m);

        $this->assertNotEmpty($m[1] ?? '', 'the login form carried no token');

        $landed = $server->request($uri, [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $m[1],
        ]);

        /* The form redirects on success, so the page itself is the next request */
        if ($landed->header('Status') !== null
            && str_starts_with((string) $landed->header('Status'), '302')) {
            $landed = $server->request($uri);
        }

        return $this->token($landed);
    }

    /**
     * The upload token, read back out of the page's own configuration block.
     */
    private function token(Response $response): string
    {
        $config = $this->jsConfig($response);

        $this->assertTrue(
            $config['upload']['enabled'] ?? false,
            'the page did not offer uploads'
        );

        return (string) ($config['upload']['token'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function jsConfig(Response $response): array
    {
        preg_match(
            '#<script id="__IVFI_DATA__" type="application/json">(.*?)</script>#s',
            $response->body,
            $m
        );

        $this->assertNotEmpty($m[1] ?? '', 'the page carried no configuration block');

        return json_decode($m[1], true);
    }

    /**
     * @return array{0: Response, 1: array<string, mixed>}
     */
    private function upload(
        Server $server,
        string $token,
        string $name,
        string $contents = 'payload',
        string $uri = '/'
    ): array {
        $response = $server->request($uri, [], [
            'ivfi_action' => 'upload',
            'ivfi_csrf'   => $token,
        ], [
            'ivfi_file' => ['name' => $name, 'contents' => $contents],
        ]);

        return [$response, json_decode($response->body, true) ?? []];
    }

    /**
     * @return array{0: Response, 1: array<string, mixed>}
     */
    private function createDirectory(
        Server $server,
        string $token,
        string $name,
        string $uri = '/'
    ): array {
        $response = $server->request($uri, [], [
            'ivfi_action' => 'directory',
            'ivfi_csrf'   => $token,
            'ivfi_name'   => $name,
        ]);

        return [$response, json_decode($response->body, true) ?? []];
    }

    /**
     * @return array{0: Response, 1: array<string, mixed>}
     */
    private function delete(
        Server $server,
        string $token,
        string $name,
        string $uri = '/'
    ): array {
        $response = $server->request($uri, [], [
            'ivfi_action' => 'delete',
            'ivfi_csrf'   => $token,
            'ivfi_name'   => $name,
        ]);

        return [$response, json_decode($response->body, true) ?? []];
    }

    public function testAnAuthenticatedClientIsOfferedTheEndpoint(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        $this->assertNotSame('', $token, 'no upload token reached the page');
    }

    public function testAnAcceptedFileIsWritten(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [$response, $payload] = $this->upload($server, $token, 'holiday.jpg', 'jpeg-bytes');

        $this->assertSame('201 Created', $response->header('Status'));
        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('holiday.jpg', $payload['file']['name'] ?? null);
        $this->assertSame('jpeg-bytes', file_get_contents($this->root() . '/holiday.jpg'));
    }

    public function testTheAnswerIsJsonThatIsNeverSniffedAsMarkup(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [$response] = $this->upload($server, $token, 'holiday.jpg');

        $this->assertStringStartsWith(
            'application/json', (string) $response->header('Content-Type')
        );
        $this->assertSame('nosniff', $response->header('X-Content-Type-Options'));
    }

    /**
     * The whole point of the allowlist: a name the web server would hand to an
     * interpreter never reaches the directory it serves.
     */
    #[DataProvider('executableNames')]
    public function testAnExecutableNameIsRefused(string $name): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [$response, $payload] = $this->upload($server, $token, $name, '<?php echo 1;');

        $this->assertMatchesRegularExpression(
            '/^(400|415) /',
            (string) $response->header('Status'),
            sprintf('%s was not turned away', $name)
        );
        $this->assertFalse($payload['ok'] ?? false);

        /* Nothing under any name: the check is that no file appeared at all */
        $written = array_filter(
            scandir($this->root()) ?: [],
            static fn (string $entry): bool => !in_array($entry, [
                '.', '..', 'indexer.php', 'indexer.config.php', 'incoming', 'existing.jpg',
            ], true)
                /* The lockout counter, which the fixture holds so it is torn down */
                && !str_starts_with($entry, 'ivfi-auth-')
        );

        $this->assertSame([], array_values($written), sprintf(
            '%s left something behind', $name
        ));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function executableNames(): array
    {
        return [
            'php'                => ['shell.php'],
            'phtml'              => ['shell.phtml'],
            'phar'               => ['shell.phar'],
            'uppercase php'      => ['shell.PHP'],
            'double extension'   => ['shell.php.jpg'],
            'trailing dot'       => ['shell.php.'],
            'htaccess'           => ['.htaccess'],
            'user ini'           => ['.user.ini'],
            'no extension'       => ['shell'],
            /**
             * Not executed by the server, but opened as a document it runs as
             * this origin, which is the origin holding the session cookie.
             * `svg` is in the default media extensions, so an allowlist of
             * "images and video" would otherwise accept it
             */
            'svg'                => ['drawing.svg'],
            'html'               => ['page.html'],
            'xhtml'              => ['page.xhtml'],
            'svg double'         => ['drawing.svg.jpg'],
            /**
             * Trimming whitespace and leading dots in separate passes left
             * this as the dotfile `.htaccess`
             */
            'spaced dotfile'     => [' .htaccess'],
        ];
    }

    /**
     * The default allowlist follows the media extensions, which carry `svg`.
     * Active content is dropped from it rather than inherited.
     */
    public function testTheDefaultAllowlistDropsActiveContent(): void
    {
        $server = $this->serve();
        $this->signIn($server);

        $extensions = $this->jsConfig($server->request('/'))['upload']['extensions'] ?? [];

        $this->assertContains('jpg', $extensions);
        $this->assertNotContains('svg', $extensions, 'svg reached the upload allowlist');
    }

    /**
     * Leading whitespace used to survive long enough for the dot strip to miss
     * it, leaving a dotfile behind under a name the allowlist accepts.
     */
    public function testWhitespaceCannotSmuggleInADotfile(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $payload] = $this->upload($server, $token, ' .secret.jpg', 'x');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('secret.jpg', $payload['file']['name'] ?? null);
        $this->assertFileExists($this->root() . '/secret.jpg');
        $this->assertFileDoesNotExist($this->root() . '/.secret.jpg');
    }

    /**
     * A `post_max_size` the multipart envelope alone exhausts used to compute
     * an effective maximum of zero, which every caller reads as "no limit".
     */
    public function testATinyPostLimitDoesNotBecomeNoLimit(): void
    {
        $fixture = new Fixture('upload-tiny-post');
        $fixture->config([
            'upload' => ['enabled' => true],
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
        ]);

        $server = new Server($fixture, [
            'post_max_size' => '4K',
            'upload_max_filesize' => '2M',
        ]);

        $this->servers[] = $server;
        $this->fixtures[] = $fixture;

        $token = $this->signIn($server);
        $maximum = $this->jsConfig($server->request('/'))['upload']['maxSize'] ?? null;

        $this->assertIsInt($maximum);
        $this->assertGreaterThan(
            0, $maximum, 'the most restrictive configuration there is reported no limit'
        );

        [, $payload] = $this->upload($server, $token, 'holiday.jpg', str_repeat('a', 64));

        $this->assertFalse($payload['ok'] ?? false);
    }

    /**
     * The name is claimed by replacing the directory entry, not by writing
     * through whatever the name currently points at.
     *
     * Proven through a second hard link to the original file: `rename()` swaps
     * the entry, so the witness keeps the old contents, while anything that
     * wrote through the path would change both. That difference is also what
     * decides whether a symlink at the target is replaced or followed, and
     * unlike the concurrent case it is deterministic.
     *
     * The window between two racing requests is not covered here. Reproducing
     * it needs either real concurrency, which makes the test a coin flip, or a
     * hook in the endpoint that exists only for the test.
     */
    public function testTheNameIsClaimedByReplacingTheDirectoryEntry(): void
    {
        $server = $this->serve(['overwrite' => true]);
        $token = $this->signIn($server);

        $target = $this->root() . '/existing.jpg';
        $witness = $this->root() . '/witness.jpg';

        if (!@link($target, $witness)) {
            $this->markTestSkipped('The filesystem does not support hard links');
        }

        [, $payload] = $this->upload($server, $token, 'existing.jpg', 'replacement');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('replacement', file_get_contents($target));
        $this->assertSame(
            'original',
            file_get_contents($witness),
            'the upload wrote through the name instead of replacing the entry'
        );
    }

    /**
     * Staging happens inside the target directory, so nothing may be left
     * behind under the working name.
     *
     * The successful case is the one that proves anything: it is the only one
     * here that reaches staging at all, and so the only one that exercises the
     * cleanup after the name is claimed. A refused upload is checked too, but
     * it is turned away before a staged file exists. The failure paths after
     * staging, where `link()` or `rename()` itself fails, are not reachable
     * without a hook in the endpoint that exists only for the test.
     */
    public function testNoStagedFileIsLeftBehind(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $accepted] = $this->upload($server, $token, 'holiday.jpg', 'jpeg-bytes');

        $this->assertTrue($accepted['ok'] ?? false);
        $this->assertSame(
            [],
            glob($this->root() . '/.ivfi-upload-*') ?: [],
            'the staged file survived a successful upload'
        );

        $this->upload($server, $token, 'existing.jpg', 'replacement');

        $this->assertSame(
            [],
            glob($this->root() . '/.ivfi-upload-*') ?: [],
            'a refused upload left a staged file behind'
        );
    }

    /**
     * A body over `post_max_size` is thrown away before the script runs, so it
     * arrives as a POST carrying neither fields nor files and nothing that says
     * it was an upload. It is recognised by that shape, because the alternative
     * is a whole listing answering a request the client is parsing as JSON.
     */
    public function testABodyOverPostMaxSizeIsAnsweredAsJson(): void
    {
        $fixture = new Fixture('upload-over-post-max');
        $fixture->config([
            'upload' => ['enabled' => true],
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
        ]);

        $server = new Server($fixture, [
            'post_max_size' => '1K',
            'upload_max_filesize' => '1K',
        ]);

        $this->servers[] = $server;
        $this->fixtures[] = $fixture;

        $token = $this->signIn($server);

        /* Comfortably past the limit, so PHP discards the body outright */
        [$response, $payload] = $this->upload(
            $server, $token, 'holiday.jpg', str_repeat('a', 8192)
        );

        $this->assertStringStartsWith('413 ', (string) $response->header('Status'));
        $this->assertStringStartsWith(
            'application/json',
            (string) $response->header('Content-Type'),
            'the client was answered with something it cannot parse'
        );
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertStringContainsString('post_max_size', $payload['error'] ?? '');
        $this->assertFileDoesNotExist($fixture->root() . '/holiday.jpg');
    }

    /**
     * Shared hosts routinely put `link` in `disable_functions`, and on PHP 8 a
     * disabled function is gone rather than returning false: calling it raises
     * an `Error` that `@` does not suppress. Uploads there fall back to
     * `rename()` instead of failing with no JSON and an abandoned staged file.
     */
    public function testUploadsWorkWhereLinkIsDisabled(): void
    {
        $fixture = new Fixture('upload-no-link');
        $fixture->config([
            'upload' => ['enabled' => true],
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
        ]);

        $server = new Server($fixture, ['disable_functions' => 'link']);

        $this->servers[] = $server;
        $this->fixtures[] = $fixture;

        $token = $this->signIn($server);

        [$response, $payload] = $this->upload($server, $token, 'holiday.jpg', 'jpeg-bytes');

        $this->assertSame('201 Created', $response->header('Status'));
        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('jpeg-bytes', file_get_contents($fixture->root() . '/holiday.jpg'));
        $this->assertSame(
            [],
            glob($fixture->root() . '/.ivfi-upload-*') ?: [],
            'the staged file was abandoned'
        );
    }

    /**
     * PHP's ini shorthand is K, M and G only: it rejects `T` outright, warning
     * that it is an unknown multiplier and reading the value as its leading
     * digits. The parser follows that rather than being more generous, because
     * a page reporting 1 TB while the engine enforces one byte would refuse
     * every upload with a limit the client was told it was under.
     */
    public function testAnUnknownIniMultiplierIsReadTheWayPhpReadsIt(): void
    {
        $fixture = new Fixture('upload-ini-suffix');
        $fixture->config([
            'upload' => ['enabled' => true],
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
        ]);

        /**
         * On `upload_max_filesize` rather than `post_max_size`, which would be
         * read as one byte for the whole request and leave even the sign-in
         * form too large to submit
         */
        $server = new Server($fixture, ['upload_max_filesize' => '1T']);

        $this->servers[] = $server;
        $this->fixtures[] = $fixture;

        $this->signIn($server);

        $maximum = $this->jsConfig($server->request('/'))['upload']['maxSize'] ?? null;

        /* One byte, the same as what PHP itself made of `1T` */
        $this->assertSame(
            1,
            $maximum,
            'the page reported a limit the engine does not enforce'
        );
    }

    /**
     * The client is told the non-overridable lists, so a name the endpoint
     * refuses for an extension that is not the last one can be turned away
     * before it is uploaded rather than after.
     */
    public function testTheBlockedListReachesTheClient(): void
    {
        $server = $this->serve();
        $this->signIn($server);

        $blocked = $this->jsConfig($server->request('/'))['upload']['blocked'] ?? [];

        $this->assertContains('php', $blocked);
        $this->assertContains('svg', $blocked);
    }

    /**
     * An operator who lists `php` has written an upload form for a web shell,
     * most likely by copying a list from somewhere else.
     */
    public function testAnExecutableExtensionCannotBeAllowlisted(): void
    {
        $server = $this->serve(['extensions' => ['php', 'jpg']]);
        $token = $this->signIn($server);

        [, $payload] = $this->upload($server, $token, 'shell.php', '<?php echo 1;');

        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileDoesNotExist($this->root() . '/shell.php');
    }

    /**
     * Listing `html` is not enough on its own: a page served from the indexed
     * directory runs as this origin, so it stays refused unless the operator
     * has said the server sandboxes it.
     */
    public function testHtmlStaysRefusedUnlessTheServerSandboxesIt(): void
    {
        $server = $this->serve(['extensions' => ['jpg', 'html']]);
        $token = $this->signIn($server);

        $extensions = $this->jsConfig($server->request('/'))['upload']['extensions'] ?? [];

        $this->assertNotContains('html', $extensions);

        [, $payload] = $this->upload($server, $token, 'page.html', '<p>hi</p>');

        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileDoesNotExist($this->root() . '/page.html');
    }

    /**
     * Where the web server sandboxes pages, an allowlisted `html` or `htm`
     * file is accepted like any other.
     */
    #[DataProvider('sandboxedPageNames')]
    public function testAnAllowlistedPageIsAcceptedWhereTheServerSandboxesIt(string $name): void
    {
        $server = $this->serve([
            'extensions' => ['jpg', 'html', 'htm'],
            'sandboxed_html' => true,
        ]);
        $token = $this->signIn($server);

        $extensions = $this->jsConfig($server->request('/'))['upload']['extensions'] ?? [];

        $this->assertContains('html', $extensions);
        $this->assertContains('htm', $extensions);

        [$response, $payload] = $this->upload($server, $token, $name, '<p>hi</p>');

        $this->assertSame('201 Created', $response->header('Status'));
        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('<p>hi</p>', file_get_contents($this->root() . '/' . $name));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function sandboxedPageNames(): array
    {
        return [
            'html'      => ['page.html'],
            'htm'       => ['page.htm'],
            'uppercase' => ['PAGE.HTML'],
        ];
    }

    /**
     * Sandboxing pages opens `html` and `htm` and nothing else. An SVG is an
     * image everywhere else, and a page type named before the real extension
     * is served however the server reads the name, without the sandbox that is
     * keyed to the last extension.
     */
    #[DataProvider('stillRefusedWhenPagesAreSandboxed')]
    public function testSandboxingPagesOpensNothingElse(string $name): void
    {
        $server = $this->serve([
            'extensions' => ['jpg', 'html', 'svg', 'xhtml', 'xml'],
            'sandboxed_html' => true,
        ]);
        $token = $this->signIn($server);

        $extensions = $this->jsConfig($server->request('/'))['upload']['extensions'] ?? [];

        $this->assertNotContains('svg', $extensions);
        $this->assertNotContains('xhtml', $extensions);
        $this->assertNotContains('xml', $extensions);

        [, $payload] = $this->upload($server, $token, $name, '<p>hi</p>');

        $this->assertFalse($payload['ok'] ?? false, sprintf('%s was accepted', $name));
        $this->assertFileDoesNotExist($this->root() . '/' . $name);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function stillRefusedWhenPagesAreSandboxed(): array
    {
        return [
            'svg'               => ['drawing.svg'],
            'xhtml'             => ['page.xhtml'],
            'xml'               => ['feed.xml'],
            'html before jpg'   => ['page.html.jpg'],
            'php before html'   => ['shell.php.html'],
        ];
    }

    public function testANameThatClimbsOutOfTheDirectoryIsFlattened(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $payload] = $this->upload($server, $token, '../escaped.jpg', 'x', '/incoming/');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertFileExists($this->root() . '/incoming/escaped.jpg');
        $this->assertFileDoesNotExist($this->root() . '/escaped.jpg');
    }

    public function testAnUploadWithoutTheTokenIsRefused(): void
    {
        $server = $this->serve();
        $this->signIn($server);

        [$response, $payload] = $this->upload($server, 'not-the-token', 'holiday.jpg');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileDoesNotExist($this->root() . '/holiday.jpg');
    }

    public function testAnUnauthenticatedUploadIsRefused(): void
    {
        /* Authentication covers only `/incoming/`, leaving the root open */
        $server = $this->serve([], [
            'authentication' => ['restrict' => '#^/incoming/#'],
        ]);

        [$response, $payload] = $this->upload($server, 'anything', 'holiday.jpg');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileDoesNotExist($this->root() . '/holiday.jpg');
    }

    public function testUploadsAreNotOfferedWithoutAuthentication(): void
    {
        $server = $this->serve([], [], false);
        $config = $this->jsConfig($server->request('/'));

        $this->assertFalse(
            $config['upload']['enabled'] ?? false,
            'an index with no users configured offered uploads'
        );
        $this->assertArrayNotHasKey(
            'token',
            $config['upload'],
            'a token reached a page that cannot upload'
        );
    }

    public function testUploadsAreNotOfferedWhenDisabled(): void
    {
        $server = $this->serve(['enabled' => false]);
        $server->request('/');

        /* Signing in by hand, since the helper asserts the endpoint is offered */
        $login = $server->request('/');
        preg_match('#name="ivfi_csrf" value="([^"]+)"#', $login->body, $m);

        $server->request('/', [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $m[1],
        ]);

        $config = $this->jsConfig($server->request('/'));

        $this->assertFalse($config['upload']['enabled'] ?? false);
    }

    /**
     * Creating a directory reads the same `$available` the upload path does,
     * so `restrict` governs both. Asserted for both here rather than inferred
     * from the shared value, which is the thing that could quietly stop being
     * shared.
     */
    public function testRestrictKeepsWritesOutOfPathsItDoesNotCover(): void
    {
        $server = $this->serve(['restrict' => '#^/incoming/#']);
        $server->request('/');

        $login = $server->request('/');
        preg_match('#name="ivfi_csrf" value="([^"]+)"#', $login->body, $m);

        $server->request('/', [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $m[1],
        ]);

        $this->assertFalse(
            $this->jsConfig($server->request('/'))['upload']['enabled'] ?? false,
            'the root offered uploads despite the restriction'
        );

        $covered = $this->jsConfig($server->request('/incoming/'));

        $this->assertTrue(
            $covered['upload']['enabled'] ?? false,
            'the covered path did not offer uploads'
        );

        $token = (string) $covered['upload']['token'];

        [, $uploaded] = $this->upload($server, $token, 'holiday.jpg');

        $this->assertFalse(
            $uploaded['ok'] ?? false,
            'the root accepted an upload despite the restriction'
        );

        [$response, $created] = $this->createDirectory($server, $token, 'nope');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse(
            $created['ok'] ?? false,
            'the root created a directory despite the restriction'
        );
        $this->assertDirectoryDoesNotExist($this->root() . '/nope');

        /* And the covered path still accepts one, or this proves only that it is broken */
        [, $allowed] = $this->createDirectory($server, $token, 'allowed', '/incoming/');

        $this->assertTrue($allowed['ok'] ?? false);
        $this->assertDirectoryExists($this->root() . '/incoming/allowed');
    }

    public function testAnExistingFileIsNotReplacedByDefault(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [$response, $payload] = $this->upload($server, $token, 'existing.jpg', 'replacement');

        $this->assertSame('409 Conflict', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertSame('original', file_get_contents($this->root() . '/existing.jpg'));
    }

    public function testAnExistingFileIsReplacedWhenOverwriteIsOn(): void
    {
        $server = $this->serve(['overwrite' => true]);
        $token = $this->signIn($server);

        [, $payload] = $this->upload($server, $token, 'existing.jpg', 'replacement');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('replacement', file_get_contents($this->root() . '/existing.jpg'));
    }

    public function testTheAllowlistFollowsTheConfiguredExtensions(): void
    {
        $server = $this->serve(['extensions' => ['txt']]);
        $token = $this->signIn($server);

        [, $accepted] = $this->upload($server, $token, 'notes.txt', 'hello');
        [, $refused] = $this->upload($server, $token, 'holiday.jpg', 'jpeg-bytes');

        $this->assertTrue($accepted['ok'] ?? false, '.txt was refused despite being listed');
        $this->assertFalse($refused['ok'] ?? false, '.jpg was accepted despite not being listed');
    }

    public function testAFileOverTheConfiguredLimitIsRefused(): void
    {
        $server = $this->serve(['max_size' => 8]);
        $token = $this->signIn($server);

        [$response, $payload] = $this->upload(
            $server, $token, 'holiday.jpg', str_repeat('a', 64)
        );

        /* The reason phrase differs between SAPIs, so only the code is asserted */
        $this->assertStringStartsWith('413 ', (string) $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileDoesNotExist($this->root() . '/holiday.jpg');
    }

    public function testTheLimitReachesTheClientSoItCanRefuseFirst(): void
    {
        $server = $this->serve(['max_size' => 1024]);
        $this->signIn($server);

        $config = $this->jsConfig($server->request('/'));

        $this->assertSame(1024, $config['upload']['maxSize'] ?? null);
        $this->assertContains('jpg', $config['upload']['extensions'] ?? []);
    }

    /**
     * `session.auto_start` hands the script a session it did not open, and on
     * a shared host another application can have left a `user` key in it. The
     * sign-in state is taken from this script's own `authenticate()` rather
     * than from whatever is in `$_SESSION`, so that is not a sign-in here.
     *
     * The foreign session is written straight to `session.save_path` and its
     * cookie sent by hand. Starting an empty one instead would leave no `user`
     * key anywhere, and the regression this guards against would sail past.
     */
    public function testAnAmbientSessionIsNotASignIn(): void
    {
        $fixture = new Fixture('upload-ambient');
        $fixture->config(['upload' => ['enabled' => true]]);

        /* Kept inside the fixture so it is torn down with it */
        $sessions = $fixture->root() . '/sessions';

        if (!mkdir($sessions, 0700, true) && !is_dir($sessions)) {
            $this->markTestSkipped('Could not create a session directory');
        }

        $id = 'ivfiambient' . bin2hex(random_bytes(4));

        /* PHP's own session serialization: another application's signed-in user */
        file_put_contents(
            $sessions . '/sess_' . $id,
            sprintf('user|s:%d:"%s";seen|i:%d;', strlen(self::USER), self::USER, time())
        );

        $server = new Server($fixture, [
            'session.auto_start' => '1',
            'session.save_path'  => $sessions,
        ]);

        $this->servers[] = $server;
        $this->fixtures[] = $fixture;

        $cookie = ['Cookie' => 'PHPSESSID=' . $id];

        /* The session really is being adopted, or the test proves nothing */
        $this->assertSame(
            self::USER,
            $this->ambientUser($server, $cookie),
            'the foreign session was never adopted, so this covers nothing'
        );

        $config = $this->jsConfig($server->request('/', $cookie));

        $this->assertFalse(
            $config['upload']['enabled'] ?? false,
            'a user key left by another application was read as a sign-in'
        );

        $response = $server->request('/', $cookie, [
            'ivfi_action' => 'upload',
            'ivfi_csrf'   => 'anything',
        ], [
            'ivfi_file' => ['name' => 'holiday.jpg', 'contents' => 'x'],
        ]);

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse(json_decode($response->body, true)['ok'] ?? false);
        $this->assertFileDoesNotExist($fixture->root() . '/holiday.jpg');
    }

    /**
     * The `user` key PHP read back out of the session file for that request.
     *
     * Read from the file rather than from the page, which deliberately says
     * nothing about it: if PHP never adopted the session the file keeps its
     * original contents anyway, so this distinguishes "not treated as a
     * sign-in" from "never seen at all".
     *
     * @param array<string, string> $cookie
     */
    private function ambientUser(Server $server, array $cookie): ?string
    {
        $server->request('/', $cookie);

        $id = explode('=', $cookie['Cookie'], 2)[1];
        $path = end($this->fixtures)->root() . '/sessions/sess_' . $id;
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        /* `session.auto_start` rewrites the file on shutdown, keeping the key */
        preg_match('/user\|s:\d+:"([^"]*)";/', $contents, $m);

        return $m[1] ?? null;
    }

    /**
     * A name the filesystem and the listing cannot both represent is refused
     * rather than repaired into something the client never asked for.
     */
    #[DataProvider('unusableNames')]
    public function testAnUnusableNameIsRefused(string $name): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $payload] = $this->upload($server, $token, $name);

        $this->assertFalse($payload['ok'] ?? false, sprintf(
            '%s was accepted', bin2hex($name)
        ));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unusableNames(): array
    {
        return [
            'invalid utf-8'  => ["holiday\xC3(.jpg"],
            'only dots'      => ['....'],
            'empty'          => [''],
            'over 255 bytes' => [str_repeat('a', 260) . '.jpg'],
        ];
    }

    /**
     * A control character in a name is stripped rather than refused, and the
     * answer names what actually landed, so the client is never told a file
     * was written under a name that is not on disk.
     */
    public function testAControlCharacterIsStrippedFromTheName(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $payload] = $this->upload($server, $token, "holiday\n.jpg", 'jpeg-bytes');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('holiday.jpg', $payload['file']['name'] ?? null);
        $this->assertFileExists($this->root() . '/holiday.jpg');
    }

    /**
     * A trailing dot is trimmed and the file accepted, which is the behaviour
     * the client has to mirror: deriving the extension from the raw name gives
     * an empty one, and the drop would be refused for a file the endpoint takes.
     */
    public function testATrailingDotIsTrimmedRatherThanRefused(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $payload] = $this->upload($server, $token, 'holiday.jpg.', 'jpeg-bytes');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('holiday.jpg', $payload['file']['name'] ?? null);
        $this->assertFileExists($this->root() . '/holiday.jpg');
    }

    public function testADirectoryIsCreated(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [$response, $payload] = $this->createDirectory($server, $token, 'holiday photos');

        $this->assertSame('201 Created', $response->header('Status'));
        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('holiday photos', $payload['directory']['name'] ?? null);
        $this->assertDirectoryExists($this->root() . '/holiday photos');
    }

    public function testADirectoryIsCreatedInThePathItWasAskedFor(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $payload] = $this->createDirectory($server, $token, 'nested', '/incoming/');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertDirectoryExists($this->root() . '/incoming/nested');
        $this->assertDirectoryDoesNotExist($this->root() . '/nested');
    }

    public function testANameThatClimbsOutIsFlattenedForDirectoriesToo(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $payload] = $this->createDirectory($server, $token, '../escaped', '/incoming/');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertDirectoryExists($this->root() . '/incoming/escaped');
        $this->assertDirectoryDoesNotExist($this->root() . '/escaped');
    }

    /**
     * A directory is not executed, but a typical handler mapping still routes a
     * request for one named `reports.php` to the interpreter, which answers a
     * request to browse it with a 404 instead of a listing.
     */
    #[DataProvider('unbrowsableDirectoryNames')]
    public function testADirectoryNamedForAHandlerIsRefused(string $name): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [$response, $payload] = $this->createDirectory($server, $token, $name);

        $this->assertStringStartsWith('400 ', (string) $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertDirectoryDoesNotExist($this->root() . '/' . ltrim($name, '.'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unbrowsableDirectoryNames(): array
    {
        return [
            'php'       => ['reports.php'],
            'phtml'     => ['reports.phtml'],
            'uppercase' => ['reports.PHP'],
            'middle'    => ['reports.php.stuff'],
            'svg'       => ['drawings.svg'],
        ];
    }

    /**
     * Ordinary dots are not the problem, only the ones that name a handler.
     */
    public function testADirectoryMayCarryDotsInItsName(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $payload] = $this->createDirectory($server, $token, 'v1.2.3 release');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertDirectoryExists($this->root() . '/v1.2.3 release');
    }

    public function testADirectoryCannotBeADotfile(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [, $payload] = $this->createDirectory($server, $token, ' .hidden');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('hidden', $payload['directory']['name'] ?? null);
        $this->assertDirectoryExists($this->root() . '/hidden');
        $this->assertDirectoryDoesNotExist($this->root() . '/.hidden');
    }

    public function testAnExistingNameIsNotReplacedByADirectory(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        [$response, $payload] = $this->createDirectory($server, $token, 'existing.jpg');

        $this->assertSame('409 Conflict', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertSame('original', file_get_contents($this->root() . '/existing.jpg'));
    }

    public function testCreatingADirectoryNeedsTheToken(): void
    {
        $server = $this->serve();
        $this->signIn($server);

        [$response, $payload] = $this->createDirectory($server, 'not-the-token', 'nope');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertDirectoryDoesNotExist($this->root() . '/nope');
    }

    public function testAnUnauthenticatedClientCannotCreateADirectory(): void
    {
        /* Authentication covers only `/incoming/`, leaving the root open */
        $server = $this->serve([], [
            'authentication' => ['restrict' => '#^/incoming/#'],
        ]);

        [$response, $payload] = $this->createDirectory($server, 'anything', 'nope');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertDirectoryDoesNotExist($this->root() . '/nope');
    }

    public function testDirectoriesCanBeTurnedOffWithoutTurningOffUploads(): void
    {
        $server = $this->serve(['directories' => false]);
        $token = $this->signIn($server);

        $config = $this->jsConfig($server->request('/'));

        $this->assertTrue($config['upload']['enabled'] ?? false);
        $this->assertFalse(
            $config['upload']['directories'] ?? true,
            'the page offered folder creation that is switched off'
        );

        [$response, $payload] = $this->createDirectory($server, $token, 'nope');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertDirectoryDoesNotExist($this->root() . '/nope');

        /* Uploading still works, which is the point of the separate flag */
        [, $uploaded] = $this->upload($server, $token, 'holiday.jpg', 'jpeg-bytes');

        $this->assertTrue($uploaded['ok'] ?? false);
    }

    public function testAFileIsDeleted(): void
    {
        $server = $this->serve(['delete' => true]);
        $token = $this->signIn($server);

        [$response, $payload] = $this->delete($server, $token, 'existing.jpg');

        $this->assertSame('200 OK', $response->header('Status'));
        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame(['name' => 'existing.jpg', 'type' => 'file'], $payload['deleted'] ?? null);
        $this->assertFileDoesNotExist($this->root() . '/existing.jpg');
    }

    public function testAFileIsDeletedFromThePathItWasAskedFor(): void
    {
        $server = $this->serve(['delete' => true]);
        file_put_contents($this->root() . '/incoming/existing.jpg', 'nested');
        $token = $this->signIn($server);

        [, $payload] = $this->delete($server, $token, 'existing.jpg', '/incoming/');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertFileDoesNotExist($this->root() . '/incoming/existing.jpg');
        $this->assertSame('original', file_get_contents($this->root() . '/existing.jpg'));
    }

    public function testAnEmptyDirectoryIsDeleted(): void
    {
        $server = $this->serve(['delete' => true]);
        $token = $this->signIn($server);

        [, $payload] = $this->delete($server, $token, 'incoming');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertSame('directory', $payload['deleted']['type'] ?? null);
        $this->assertDirectoryDoesNotExist($this->root() . '/incoming');
    }

    /**
     * Only an empty directory goes, so one mistaken click removes one thing at
     * most and never a tree. A dotfile the listing does not show still counts.
     */
    #[DataProvider('occupiedDirectoryContents')]
    public function testADirectoryWithSomethingInItIsNotDeleted(string $inside): void
    {
        $server = $this->serve(['delete' => true]);
        file_put_contents($this->root() . '/incoming/' . $inside, 'kept');
        $token = $this->signIn($server);

        [$response, $payload] = $this->delete($server, $token, 'incoming');

        $this->assertSame('409 Conflict', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileExists($this->root() . '/incoming/' . $inside);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function occupiedDirectoryContents(): array
    {
        return [
            'a file'   => ['holiday.jpg'],
            'a dotfile' => ['.hidden'],
        ];
    }

    /**
     * The name is matched against what the listing shows, so anything it hides
     * is out of reach, and so is a name that describes a path rather than an
     * entry of the directory being viewed.
     */
    #[DataProvider('unlistedNames')]
    public function testANameTheListingDoesNotShowIsNotDeleted(string $name, string $uri): void
    {
        $server = $this->serve(['delete' => true], [
            'filter' => ['file' => '/^(?!secret)/', 'directory' => false],
        ]);
        file_put_contents($this->root() . '/.hidden.jpg', 'kept');
        file_put_contents($this->root() . '/secret.jpg', 'kept');
        $token = $this->signIn($server, $uri);

        [$response, $payload] = $this->delete($server, $token, $name, $uri);

        $this->assertSame('404 Not Found', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileExists($this->root() . '/.hidden.jpg');
        $this->assertFileExists($this->root() . '/secret.jpg');
        $this->assertSame('original', file_get_contents($this->root() . '/existing.jpg'));
        $this->assertDirectoryExists($this->root() . '/incoming');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function unlistedNames(): array
    {
        return [
            'dotfile'          => ['.hidden.jpg', '/'],
            'filtered out'     => ['secret.jpg', '/'],
            'climbing out'     => ['../existing.jpg', '/incoming/'],
            'a nested path'    => ['incoming/../existing.jpg', '/'],
            'current dir'      => ['.', '/incoming/'],
            'parent dir'       => ['..', '/incoming/'],
            'trailing dot'     => ['existing.jpg.', '/'],
            'absolute path'    => ['/etc/hostname', '/'],
            'empty'            => ['', '/'],
            'indexer script'   => ['indexer.php', '/'],
        ];
    }

    /**
     * Where the index is served from the web root, the indexer's configuration
     * sits in the listing beside everything else. It is not something a
     * signed-in client should be able to take down.
     */
    #[DataProvider('runnableNames')]
    public function testAFileTheServerCanRunIsNotDeleted(string $name): void
    {
        $server = $this->serve(['delete' => true]);

        if (!is_file($this->root() . '/' . $name)) {
            file_put_contents($this->root() . '/' . $name, '<?php');
        }

        $token = $this->signIn($server);

        [$response, $payload] = $this->delete($server, $token, $name);

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileExists($this->root() . '/' . $name);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function runnableNames(): array
    {
        return [
            'indexer config' => ['indexer.config.php'],
            'uppercase'      => ['notes.PHP'],
            'middle'         => ['payload.php.jpg'],
        ];
    }

    /**
     * A link is removed as a link. What it points at stays, even when that is a
     * directory with things in it.
     */
    public function testALinkIsRemovedWithoutTouchingItsTarget(): void
    {
        $server = $this->serve(['delete' => true]);
        file_put_contents($this->root() . '/incoming/holiday.jpg', 'kept');

        if (!@symlink($this->root() . '/incoming', $this->root() . '/shortcut')) {
            $this->markTestSkipped('symlinks are not available here');
        }

        $token = $this->signIn($server);

        [, $payload] = $this->delete($server, $token, 'shortcut');

        $this->assertTrue($payload['ok'] ?? false);
        $this->assertFalse(is_link($this->root() . '/shortcut'));
        $this->assertFileExists($this->root() . '/incoming/holiday.jpg');
    }

    public function testDeletingNeedsTheToken(): void
    {
        $server = $this->serve(['delete' => true]);
        $this->signIn($server);

        [$response, $payload] = $this->delete($server, 'not-the-token', 'existing.jpg');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileExists($this->root() . '/existing.jpg');
    }

    public function testAnUnauthenticatedClientCannotDelete(): void
    {
        /* Authentication covers only `/incoming/`, leaving the root open */
        $server = $this->serve(['delete' => true], [
            'authentication' => ['restrict' => '#^/incoming/#'],
        ]);

        [$response, $payload] = $this->delete($server, 'anything', 'existing.jpg');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileExists($this->root() . '/existing.jpg');
    }

    /**
     * Deleting is the one write that cannot be undone, so it stays off until
     * it is asked for, and the page is not told about an endpoint it cannot use.
     */
    public function testDeletingIsOffUnlessTurnedOn(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        $config = $this->jsConfig($server->request('/'));

        $this->assertFalse(
            $config['upload']['delete'] ?? true,
            'the page offered deleting that is switched off'
        );

        [$response, $payload] = $this->delete($server, $token, 'existing.jpg');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileExists($this->root() . '/existing.jpg');
    }

    public function testThePageIsToldWhenDeletingIsOn(): void
    {
        $server = $this->serve(['delete' => true]);
        $this->signIn($server);

        $config = $this->jsConfig($server->request('/'));

        $this->assertTrue($config['upload']['delete'] ?? false);
        $this->assertSame('delete', $config['upload']['deleteAction'] ?? null);
    }

    public function testAnUploadedFileIsReadableByTheServer(): void
    {
        $server = $this->serve();
        $token = $this->signIn($server);

        $this->upload($server, $token, 'holiday.jpg', 'jpeg-bytes');

        $this->assertSame(
            '0644',
            substr(sprintf('%o', fileperms($this->root() . '/holiday.jpg')), -4)
        );
    }
}
