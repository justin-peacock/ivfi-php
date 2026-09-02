<?php

declare(strict_types=1);

namespace Ivfi\Tests\Security;

use Ivfi\Tests\Support\Fixture;
use Ivfi\Tests\Support\Indexer;
use Ivfi\Tests\Support\IndexerTestCase;

/**
 * Containment was decided by a plain string prefix, so a sibling directory
 * whose name merely began with the base was treated as inside it.
 *
 * These render the script directly rather than through a web server, because a
 * server normalises `..` out of the request before PHP ever sees it. That is
 * what kept the weakness from being reachable in most deployments, and it is
 * also what makes it worth testing here: this is the check itself, with no
 * server in front of it.
 */
final class PathContainmentTest extends IndexerTestCase
{
    public function testSiblingDirectorySharingThePrefixIsRefused(): void
    {
        $fixture = new Fixture('containment');
        $fixture->file('public.txt');

        /* A sibling whose path starts with the base path, byte for byte */
        $sibling = $fixture->root() . '-secret';

        mkdir($sibling, 0700, true);
        file_put_contents($sibling . '/classified.txt', 'top secret');

        try {
            $response = Indexer::render(
                $fixture, '/../' . basename($sibling) . '/'
            );

            $this->assertStringNotContainsString(
                'classified.txt',
                $response->body,
                'a sibling directory sharing the base prefix was listed'
            );
        } finally {
            @unlink($sibling . '/classified.txt');
            @rmdir($sibling);
        }
    }

    public function testParentDirectoryIsRefused(): void
    {
        $fixture = new Fixture('containment-parent');
        $fixture->file('public.txt');

        $response = Indexer::render($fixture, '/../');

        $this->assertStringNotContainsString('ivfi-containment-parent', $response->rows());
    }

    public function testTraversalOutsideTheBaseIsRefused(): void
    {
        $fixture = new Fixture('containment-traversal');
        $fixture->file('public.txt');

        $response = Indexer::render($fixture, '/../../../../etc/');

        $this->assertStringNotContainsString('passwd', $response->body);
    }

    /**
     * The fix must not cost legitimate nesting, which is the whole point of
     * the script.
     */
    public function testRealSubdirectoriesAreStillListed(): void
    {
        $fixture = new Fixture('containment-nested');
        $fixture->directory('albums');
        $fixture->directory('albums/2024');
        $fixture->file('albums/2024/photo.jpg');

        $response = Indexer::render($fixture, '/albums/2024/');

        $this->assertStringContainsString('photo.jpg', $response->body);
    }

    public function testDeeplyNestedDirectoriesAreStillListed(): void
    {
        $fixture = new Fixture('containment-deep');
        $fixture->directory('a/b/c/d/e');
        $fixture->file('a/b/c/d/e/deep.txt');

        $response = Indexer::render($fixture, '/a/b/c/d/e/');

        $this->assertStringContainsString('deep.txt', $response->body);
    }
}
