<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\IndexerTestCase;
use Ivfi\Tests\Support\Response;
use Ivfi\Tests\Support\Server;

/**
 * Read-only share links, which let someone browse one directory without the
 * password.
 *
 * A share is a capability handed to a stranger, so what matters is what it
 * does *not* reach: the directory above it, a sibling whose name begins the
 * same way, the root, and every write the signed-in client has. These run
 * through PHP's built-in server, because the exchange is a redirect, a cookie
 * and a status, none of which the CLI reports.
 */
final class ShareLinkTest extends IndexerTestCase
{
    private const USER = 'emy';
    private const PASS = 'correct horse battery staple';

    /** @var list<Server> */
    private array $servers = [];

    protected function tearDown(): void
    {
        foreach ($this->servers as $server) {
            $server->stop();
        }

        $this->servers = [];
    }

    /**
     * A tree with a directory worth sharing, a sibling that must stay out of
     * reach, and a nested directory the share is expected to cover.
     *
     * @param array<string, mixed> $share Overrides for the `share` config
     */
    private function serve(array $share = []): Server
    {
        $fixture = new Fixture('share-link');

        $fixture->file('root-secret.txt');
        $fixture->directory('holiday');
        $fixture->file('holiday/beach.jpg');
        $fixture->directory('holiday/day-two');
        $fixture->file('holiday/day-two/sunset.jpg');

        /* Shares `/holiday/` as a prefix, so this must not be covered */
        $fixture->directory('holiday-invoices');
        $fixture->file('holiday-invoices/unpaid.pdf');

        $fixture->directory('private');
        $fixture->file('private/payslip.pdf');

        $fixture->config([
            'authentication' => [
                'users' => [
                    self::USER => password_hash(self::PASS, PASSWORD_DEFAULT),
                ],
                /* Kept inside the fixture so it is torn down with it */
                'throttle_path' => $fixture->root(),
            ],
            'share' => array_merge([
                'enabled' => true,
                'path' => $fixture->root() . '/state',
            ], $share),
        ]);

        $server = new Server($fixture, []);
        $this->servers[] = $server;

        return $server;
    }

    private function token(Response $response): string
    {
        preg_match('#name="ivfi_csrf" value="([^"]+)"#', $response->body, $m);

        $this->assertNotEmpty($m[1] ?? '', 'the login form carried no token');

        return $m[1];
    }

    private function signIn(Server $server): void
    {
        $login = $server->request('/');

        $server->request('/', [], [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $this->token($login),
        ]);
    }

    /**
     * The page's own token, which the share endpoint checks the way the
     * upload one does.
     */
    private function pageToken(Server $server, string $uri): string
    {
        $page = $server->request($uri);

        preg_match('#"token":"([a-f0-9]+)"#', $page->body, $m);

        $this->assertNotEmpty(
            $m[1] ?? '', "the page at {$uri} carried no share token"
        );

        return $m[1];
    }

    /**
     * Signs in and mints a link for a directory, answering with its token.
     *
     * @param array<string, string> $extra Extra fields for the request
     */
    private function mint(
        Server $server,
        string $uri = '/holiday/',
        array $extra = []
    ): string {
        $this->signIn($server);

        $response = $server->request($uri, [], array_merge([
            'ivfi_share_action' => 'create',
            'ivfi_csrf' => $this->pageToken($server, $uri),
        ], $extra));

        $payload = json_decode($response->body, true);

        $this->assertIsArray($payload, "minting answered with {$response->body}");
        $this->assertTrue(
            $payload['ok'] ?? false,
            'minting failed: ' . ($payload['error'] ?? 'no reason given')
        );

        $token = $payload['share']['token'] ?? '';

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/', $token, 'the minted token is not 32 random bytes'
        );

        return $token;
    }

    /**
     * Follows a link the way a recipient does: as a browser with no session
     * of its own, which then keeps whatever cookie came back.
     */
    private function follow(Server $server, string $uri, string $token): Response
    {
        $server->clearCookies();

        $arrival = $server->request(sprintf(
            '%s?ivfi_share=%s', $uri, rawurlencode($token)
        ));

        $this->assertSame(
            '302 Found',
            $arrival->header('Status'),
            'following a link did not redirect the token out of the address'
        );

        /* The jar picked up the share cookie, so the next request carries it */
        return $server->request($uri);
    }

    public function testWithoutALinkTheDirectoryIsBehindThePassword(): void
    {
        $response = $this->serve()->request('/holiday/');

        $this->assertSame('401 Unauthorized', $response->header('Status'));
        $this->assertStringNotContainsString('beach.jpg', $response->body);
    }

    public function testFollowingALinkListsTheSharedDirectory(): void
    {
        $server = $this->serve();
        $token = $this->mint($server);

        $listing = $this->follow($server, '/holiday/', $token);

        $this->assertStringContainsString('beach.jpg', $listing->body);
        $this->assertStringNotContainsString('name="ivfi_pass"', $listing->body);
    }

    /**
     * The token is taken out of the address on arrival, so it does not sit in
     * the address bar, in the forward history, or in anything that later reads
     * a URL back out of the page.
     */
    public function testTheTokenIsExchangedForACookieAndLeavesTheAddress(): void
    {
        $server = $this->serve();
        $token = $this->mint($server);

        $server->clearCookies();

        $arrival = $server->request('/holiday/?ivfi_share=' . $token);

        $this->assertSame('302 Found', $arrival->header('Status'));
        $this->assertSame('/holiday/', $arrival->header('Location'));
        $this->assertStringNotContainsString(
            $token, (string) $arrival->header('Location')
        );

        $this->assertSame(
            $token,
            $arrival->setCookies()['IVFISHARE'] ?? null,
            'the share token was not handed back as a cookie'
        );
    }

    /**
     * The cookie is scoped to the shared directory, so a browser never offers
     * it anywhere else. That is what keeps the page's own links free of the
     * token.
     */
    public function testTheShareCookieIsScopedAndProtected(): void
    {
        $server = $this->serve();
        $token = $this->mint($server);

        $server->clearCookies();

        $attributes = $server->request(
            '/holiday/?ivfi_share=' . $token
        )->cookieAttributes('IVFISHARE');

        $this->assertContains(
            'path=/holiday/',
            $attributes,
            'the share cookie was not scoped to the shared directory'
        );

        $this->assertContains('httponly', $attributes);
        $this->assertContains('samesite=lax', $attributes);
    }

    public function testALinkCoversDirectoriesUnderTheSharedOne(): void
    {
        $server = $this->serve();
        $token = $this->mint($server);

        $this->follow($server, '/holiday/', $token);

        /* The cookie is already held, the way a browser would hold it */
        $nested = $server->request('/holiday/day-two/');

        $this->assertStringContainsString('sunset.jpg', $nested->body);
    }

    /**
     * The whole point of the containment check. A share of `/holiday/` must
     * not read as covering `/holiday-invoices/`, which a plain string prefix
     * would accept.
     */
    public function testALinkDoesNotCoverASiblingSharingThePrefix(): void
    {
        $server = $this->serve();
        $token = $this->mint($server);

        $this->follow($server, '/holiday/', $token);

        $sibling = $server->request('/holiday-invoices/');

        $this->assertSame('401 Unauthorized', $sibling->header('Status'));
        $this->assertStringNotContainsString('unpaid.pdf', $sibling->body);
    }

    public function testALinkDoesNotCoverTheParentOrASibling(): void
    {
        $server = $this->serve();
        $token = $this->mint($server);

        $this->follow($server, '/holiday/', $token);

        foreach (['/' => 'root-secret.txt', '/private/' => 'payslip.pdf'] as $uri => $entry) {
            $response = $server->request($uri);

            $this->assertSame(
                '401 Unauthorized',
                $response->header('Status'),
                "a share of /holiday/ opened {$uri}"
            );

            $this->assertStringNotContainsString($entry, $response->body);
        }
    }

    /**
     * `REQUEST_URI` is the request line as it arrived, not a resolved path, so
     * the share gate folds `..` away itself. Without that, a link to one
     * directory would walk out of it into another.
     *
     * @param string $uri A traversal out of the shared directory
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('traversals')]
    public function testALinkCannotBeWalkedOutOf(string $uri): void
    {
        $server = $this->serve();
        $token = $this->mint($server);

        $this->follow($server, '/holiday/', $token);

        $response = $server->request($uri);

        $this->assertStringNotContainsString(
            'payslip.pdf',
            $response->body,
            "{$uri} escaped the shared directory"
        );

        $this->assertStringNotContainsString('root-secret.txt', $response->body);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function traversals(): array
    {
        return [
            'dot segments'       => ['/holiday/../private/'],
            'encoded dots'       => ['/holiday/%2e%2e/private/'],
            'doubled separators' => ['/holiday//..//private/'],
            'to the root'        => ['/holiday/../'],
            'trailing dot'       => ['/holiday/./../private/'],
        ];
    }

    /**
     * The request path and the directory it names are not the same string.
     *
     * `CURRENT_URI` is decoded once and the Indexer decodes it again, so a
     * doubly encoded traversal folds away for one and not the other; a `#` in
     * a directory name decodes into something a reader of URIs truncates at;
     * and a symbolic link makes a path inside the share resolve outside it.
     * Each of these listed an unshared directory to a share viewer.
     *
     * @param string $uri   A request that escapes the shared directory
     * @param string $entry Something only the escaped directory holds
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('escapes')]
    public function testAPathThatResolvesOutsideTheShareIsRefused(
        string $uri,
        string $entry
    ): void {
        $fixture = new Fixture('share-resolve');

        $fixture->directory('holiday');
        $fixture->file('holiday/beach.jpg');
        $fixture->directory('private');
        $fixture->file('private/payslip.pdf');

        /* A sibling whose name only differs once the request is decoded */
        $fixture->directory('holiday#private');
        $fixture->file('holiday#private/hashleak.txt');

        /* And a way back out of the share that no string comparison can see */
        @symlink('..', $fixture->root() . '/holiday/up');

        $fixture->config([
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
            'share' => [
                'enabled' => true,
                'path' => $fixture->root() . '/state',
            ],
        ]);

        $server = new Server($fixture, []);
        $this->servers[] = $server;

        $token = $this->mint($server);

        $this->follow($server, '/holiday/', $token);

        $response = $server->request($uri);

        $this->assertStringNotContainsString(
            $entry,
            $response->body,
            "{$uri} was listed to a share of /holiday/"
        );

        $this->assertStringNotContainsString('<tr class="file"', $response->body);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function escapes(): array
    {
        return [
            'doubly encoded traversal' => ['/holiday/%252e%252e/private/', 'payslip.pdf'],
            'encoded hash in a name'   => ['/holiday%23private/', 'hashleak.txt'],
            'symlink to the parent'    => ['/holiday/up/private/', 'payslip.pdf'],
            'symlink to the root'      => ['/holiday/up/', 'holiday'],
        ];
    }

    /**
     * The cookie's `Path` is matched against the request target, which is
     * encoded, so a share of a directory whose name needs encoding has to be
     * scoped to the encoded form or the browser never sends it back.
     */
    public function testAShareOfANameNeedingEncodingWorks(): void
    {
        $fixture = new Fixture('share-encoding');

        $fixture->directory('summer photos');
        $fixture->file('summer photos/pic.jpg');

        $fixture->config([
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
            'share' => [
                'enabled' => true,
                'path' => $fixture->root() . '/state',
            ],
        ]);

        $server = new Server($fixture, []);
        $this->servers[] = $server;

        $this->signIn($server);

        $response = $server->request('/summer%20photos/', [], [
            'ivfi_share_action' => 'create',
            'ivfi_csrf' => $this->pageToken($server, '/summer%20photos/'),
        ]);

        $payload = json_decode($response->body, true);

        $this->assertTrue($payload['ok'] ?? false, $response->body);

        /* The stored path stays decoded; the public one is what a browser uses */
        $this->assertSame('/summer photos/', $payload['share']['path']);
        $this->assertSame('/summer%20photos/', $payload['share']['url']);

        $server->clearCookies();

        $arrival = $server->request(
            '/summer%20photos/?ivfi_share=' . $payload['share']['token']
        );

        $this->assertContains(
            'path=/summer%20photos/',
            $arrival->cookieAttributes('IVFISHARE'),
            'the cookie was scoped to the decoded name, which is never sent back'
        );

        $this->assertStringContainsString(
            'pic.jpg', $server->request('/summer%20photos/')->body
        );
    }

    /**
     * Behind a proxy mount the listing's own links carry the prepend, so the
     * link handed to a recipient and the cookie scope have to as well.
     */
    public function testAShareLinkCarriesThePrependPath(): void
    {
        $fixture = new Fixture('share-prepend');

        $fixture->directory('holiday');
        $fixture->file('holiday/beach.jpg');

        $fixture->config([
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
            'share' => [
                'enabled' => true,
                'path' => $fixture->root() . '/state',
            ],
            'trust_prepend_header' => true,
        ]);

        $server = new Server($fixture, []);
        $this->servers[] = $server;

        $header = ['X-Indexer-Prepend-Path' => '/file/upstream/'];

        $login = $server->request('/', $header);

        $server->request('/', $header, [
            'ivfi_user' => self::USER,
            'ivfi_pass' => self::PASS,
            'ivfi_csrf' => $this->token($login),
        ]);

        $page = $server->request('/holiday/', $header);

        preg_match('#"token":"([a-f0-9]+)"#', $page->body, $m);

        $response = $server->request('/holiday/', $header, [
            'ivfi_share_action' => 'create',
            'ivfi_csrf' => $m[1] ?? '',
        ]);

        $payload = json_decode($response->body, true);

        $this->assertTrue($payload['ok'] ?? false, $response->body);

        /* Authorisation still compares the path the script is actually asked for */
        $this->assertSame('/holiday/', $payload['share']['path']);

        /* The recipient's link and cookie use the route the proxy publishes */
        $this->assertSame('/file/upstream/holiday/', $payload['share']['url']);

        $server->clearCookies();

        $arrival = $server->request(
            '/holiday/?ivfi_share=' . $payload['share']['token'], $header
        );

        $this->assertContains(
            'path=/file/upstream/holiday/',
            $arrival->cookieAttributes('IVFISHARE')
        );
    }

    /**
     * The deny file is what keeps the store from being served where the web
     * server hands out dotfiles, so a directory the operator made in advance
     * has to get one too.
     */
    public function testAPrecreatedStoreDirectoryIsStillProtected(): void
    {
        $fixture = new Fixture('share-precreated');

        $fixture->directory('holiday');
        $fixture->file('holiday/beach.jpg');

        $state = $fixture->root() . '/state';

        /* Made before anything is minted, the way an operator would */
        mkdir($state, 0700, true);

        $fixture->config([
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
            'share' => [
                'enabled' => true,
                'path' => $state,
            ],
        ]);

        $server = new Server($fixture, []);
        $this->servers[] = $server;

        $this->mint($server);

        $this->assertFileExists(
            $state . '/.htaccess',
            'a store directory that already existed was left unprotected'
        );
    }

    /**
     * A share is granted without a session, so every write in the script,
     * which follows that, stays closed to it.
     */
    public function testAShareViewerIsOfferedNoWrites(): void
    {
        $fixture = new Fixture('share-readonly');

        $fixture->directory('holiday');
        $fixture->file('holiday/beach.jpg');

        $fixture->config([
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
            /* Everything a signed-in client would be given */
            'upload' => [
                'enabled' => true,
                'directories' => true,
                'delete' => true,
            ],
            'share' => [
                'enabled' => true,
                'path' => $fixture->root() . '/state',
            ],
        ]);

        $server = new Server($fixture, []);
        $this->servers[] = $server;

        $token = $this->mint($server);
        $listing = $this->follow($server, '/holiday/', $token);

        /* The page describes no upload to a client that cannot make one */
        $this->assertStringContainsString('"upload":{"enabled":false}', $listing->body);
        $this->assertStringContainsString('"share":{"enabled":false}', $listing->body);

        /**
         * And the endpoints refuse it whatever the page says. A real
         * multipart body for the upload, since the endpoint recognizes one by
         * its content type and a urlencoded POST would never reach the check
         * being tested here
         */
        $upload = $server->request('/holiday/', [], [
            'ivfi_action' => 'upload',
        ], [
            'ivfi_file' => ['name' => 'mine.jpg', 'contents' => 'x'],
        ]);

        $this->assertContains(
            $upload->header('Status'),
            ['401 Unauthorized', '403 Forbidden'],
            'a share viewer was allowed to upload'
        );

        foreach ([
            'folder' => ['ivfi_action' => 'directory', 'ivfi_name' => 'mine'],
            'delete' => ['ivfi_action' => 'delete', 'ivfi_name' => 'beach.jpg'],
            'minting' => ['ivfi_share_action' => 'create'],
        ] as $label => $fields) {
            $response = $server->request('/holiday/', [], $fields);

            $this->assertContains(
                $response->header('Status'),
                ['401 Unauthorized', '403 Forbidden', '404 Not Found'],
                "a share viewer was allowed to attempt {$label}"
            );
        }

        /* Nothing was written, and nothing was taken away */
        $this->assertFileExists($fixture->root() . '/holiday/beach.jpg');
        $this->assertFileDoesNotExist($fixture->root() . '/holiday/mine');
        $this->assertFileDoesNotExist($fixture->root() . '/holiday/mine.jpg');
    }

    /**
     * A link to the root would hand over every directory at once and leave
     * the password guarding nothing.
     */
    public function testTheRootCannotBeShared(): void
    {
        $server = $this->serve();

        $this->signIn($server);

        $response = $server->request('/', [], [
            'ivfi_share_action' => 'create',
            'ivfi_csrf' => $this->pageToken($server, '/'),
        ]);

        $payload = json_decode($response->body, true);

        $this->assertFalse($payload['ok'] ?? true, 'the root was shared');
        $this->assertSame('400 Bad Request', $response->header('Status'));
        $this->assertStringContainsString('root cannot be shared', $payload['error'] ?? '');
    }

    /**
     * Minting hands out access, so it is an authenticated action: a share
     * cannot be used to mint another, and neither can a stranger.
     */
    public function testMintingNeedsASession(): void
    {
        $server = $this->serve();

        $response = $server->request('/holiday/', [], [
            'ivfi_share_action' => 'create',
            'ivfi_csrf' => 'whatever',
        ]);

        $this->assertNotSame('201 Created', $response->header('Status'));
        $this->assertStringNotContainsString('"token"', $response->body);
    }

    public function testMintingNeedsThePageToken(): void
    {
        $server = $this->serve();

        $this->signIn($server);

        $response = $server->request('/holiday/', [], [
            'ivfi_share_action' => 'create',
            'ivfi_csrf' => str_repeat('0', 64),
        ]);

        $payload = json_decode($response->body, true);

        $this->assertSame('403 Forbidden', $response->header('Status'));
        $this->assertFalse($payload['ok'] ?? true);
    }

    public function testARevokedLinkStopsWorking(): void
    {
        $server = $this->serve();
        $token = $this->mint($server);

        /* It works first, so the revocation is what changes the answer */
        $this->assertStringContainsString(
            'beach.jpg', $this->follow($server, '/holiday/', $token)->body
        );

        $this->signIn($server);

        $revoked = $server->request('/holiday/', [], [
            'ivfi_share_action' => 'revoke',
            'ivfi_share_token' => $token,
            'ivfi_csrf' => $this->pageToken($server, '/holiday/'),
        ]);

        $this->assertTrue(json_decode($revoked->body, true)['ok'] ?? false);

        $server->clearCookies();

        $response = $server->request('/holiday/?ivfi_share=' . $token);

        $this->assertSame('401 Unauthorized', $response->header('Status'));
        $this->assertStringNotContainsString('beach.jpg', $response->body);
    }

    public function testAnExpiredLinkStopsWorking(): void
    {
        $server = $this->serve();

        /* One second, so it has lapsed by the time it is followed */
        $token = $this->mint($server, '/holiday/', [
            'ivfi_share_expiry' => '1',
        ]);

        sleep(2);

        $server->clearCookies();

        $response = $server->request('/holiday/?ivfi_share=' . $token);

        $this->assertSame('401 Unauthorized', $response->header('Status'));
        $this->assertStringNotContainsString('beach.jpg', $response->body);
    }

    /**
     * @param string $token Something shaped like a token but not one of ours
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('rejectedTokens')]
    public function testAnUnknownTokenGetsThePassword(string $token): void
    {
        $server = $this->serve();

        /* One real link exists, so the store is not simply empty */
        $this->mint($server);

        $server->clearCookies();

        $response = $server->request('/holiday/?ivfi_share=' . $token);

        $this->assertSame('401 Unauthorized', $response->header('Status'));
        $this->assertStringNotContainsString('beach.jpg', $response->body);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rejectedTokens(): array
    {
        return [
            'wrong value'  => [str_repeat('a', 64)],
            'too short'    => [str_repeat('a', 32)],
            'not hex'      => [str_repeat('z', 64)],
            'empty'        => [''],
            'path shaped'  => ['../../etc/passwd'],
        ];
    }

    /**
     * Someone who followed a link is shown that they are in one directory of
     * a larger index, and is offered no way up out of it: those links answer
     * with a password form, which reads as something having gone wrong.
     */
    public function testTheSharedPageOffersNoWayUp(): void
    {
        $server = $this->serve();
        $token = $this->mint($server);

        $listing = $this->follow($server, '/holiday/', $token);

        $this->assertStringContainsString('shareInfo', $listing->body);
        $this->assertStringContainsString('read-only', $listing->body);

        $this->assertStringNotContainsString(
            '<tr class="parent">',
            $listing->body,
            'the top of a share offered a link to the directory above it'
        );

        /* The path is shown, but nothing above the share is a link */
        $this->assertStringNotContainsString('<a href="/">/</a>', $listing->body);

        /* A subdirectory of the share may still go back up to it */
        $nested = $server->request('/holiday/day-two/');

        $this->assertStringContainsString('<tr class="parent">', $nested->body);
    }

    /**
     * With shares switched off, a token that was minted while they were on is
     * not honored and the parameter does nothing.
     */
    public function testATokenDoesNothingWhileSharesAreDisabled(): void
    {
        $fixture = new Fixture('share-disabled');

        $fixture->directory('holiday');
        $fixture->file('holiday/beach.jpg');

        $state = $fixture->root() . '/state';

        mkdir($state, 0700, true);

        $fixture->config([
            'authentication' => [
                'users' => [self::USER => password_hash(self::PASS, PASSWORD_DEFAULT)],
                'throttle_path' => $fixture->root(),
            ],
            'share' => [
                'enabled' => false,
                'path' => $state,
            ],
        ]);

        $server = new Server($fixture, []);
        $this->servers[] = $server;

        $response = $server->request('/holiday/?ivfi_share=' . str_repeat('a', 64));

        $this->assertSame('401 Unauthorized', $response->header('Status'));
        $this->assertStringNotContainsString('beach.jpg', $response->body);
    }

    /**
     * The store holds live capabilities, so it is not readable by anyone else
     * on the host.
     */
    public function testTheStoreIsNotWorldReadable(): void
    {
        $server = $this->serve();

        $this->mint($server);

        $files = glob(sys_get_temp_dir() . '/ivfi-share-link-*/state/ivfi-shares-*.json');

        $this->assertNotEmpty($files, 'the share store was never written');

        foreach ($files as $file) {
            $this->assertSame(
                0600,
                fileperms($file) & 0777,
                'the share store is readable beyond the web server'
            );
        }
    }
}
