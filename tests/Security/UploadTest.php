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
        ];
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

    public function testRestrictKeepsUploadsOutOfPathsItDoesNotCover(): void
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

        [, $payload] = $this->upload(
            $server, (string) $covered['upload']['token'], 'holiday.jpg'
        );

        $this->assertFalse(
            $payload['ok'] ?? false,
            'the root accepted an upload despite the restriction'
        );
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
     */
    public function testAnAmbientSessionIsNotASignIn(): void
    {
        $fixture = new Fixture('upload-ambient');
        $fixture->config(['upload' => ['enabled' => true]]);

        $server = new Server($fixture, ['session.auto_start' => '1']);

        $this->servers[] = $server;
        $this->fixtures[] = $fixture;

        $config = $this->jsConfig($server->request('/'));

        $this->assertFalse(
            $config['upload']['enabled'] ?? false,
            'an auto-started session was read as a sign-in'
        );

        [$response, $payload] = $this->upload($server, 'anything', 'holiday.jpg');

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? false);
        $this->assertFileDoesNotExist($fixture->root() . '/holiday.jpg');
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
