<?php

declare(strict_types=1);

namespace Ivfi\Tests\Support;

/**
 * A throwaway directory tree with a copy of the built indexer in it.
 *
 * Entries are created at run time rather than committed, because several of
 * the names the suite needs to cover cannot live in a git tree on every
 * platform.
 */
final class Fixture
{
    private string $root;

    /** @var list<string> */
    private array $skipped = [];

    public function __construct(string $label = 'fixture')
    {
        $this->root = sprintf(
            '%s/ivfi-%s-%s',
            sys_get_temp_dir(),
            preg_replace('/[^a-z0-9]+/i', '-', $label),
            bin2hex(random_bytes(6))
        );

        if (!mkdir($this->root, 0700, true) && !is_dir($this->root)) {
            throw new \RuntimeException("Could not create fixture at {$this->root}");
        }

        $build = __DIR__ . '/../../build/indexer.php';

        /**
         * Fail here rather than letting every test in the class report a
         * confusing "path does not exist" against a fixture with no script in
         * it. The directory is removed first, because a constructor that
         * throws leaves an object the destructor never runs for.
         */
        if (!is_file($build) || !copy($build, $this->root . '/indexer.php')) {
            $this->remove($this->root);

            throw new \RuntimeException(
                "Could not copy {$build} into the fixture at {$this->root}"
            );
        }
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * Names the entries the filesystem refused, so a test can report what it
     * was unable to cover rather than silently passing on a smaller set.
     *
     * @return list<string>
     */
    public function skipped(): array
    {
        return $this->skipped;
    }

    /**
     * Creates a file, returning false when the filesystem rejects the name.
     */
    public function file(string $name, string $contents = 'x'): bool
    {
        $path = $this->root . '/' . $name;

        if (@file_put_contents($path, $contents) === false) {
            $this->skipped[] = $name;

            return false;
        }

        /* Fixed timestamp so golden output does not move with the clock */
        touch($path, 1704110400);

        return true;
    }

    /**
     * Creates a directory, returning false when the filesystem rejects it.
     */
    public function directory(string $name): bool
    {
        $path = $this->root . '/' . $name;

        if (!@mkdir($path, 0700, true) && !is_dir($path)) {
            $this->skipped[] = $name;

            return false;
        }

        touch($path, 1704110400);

        return true;
    }

    /**
     * Writes an `indexer.config.php` next to the script.
     *
     * @param array<string, mixed> $config
     */
    public function config(array $config): void
    {
        $path = $this->root . '/indexer.config.php';

        file_put_contents(
            $path,
            "<?php\nreturn " . var_export($config, true) . ";\n"
        );

        /**
         * Pinned like every other entry: the config sits in the directory it
         * configures, so it is listed, and a live timestamp on it would move
         * the rendered date on every run
         */
        touch($path, 1704110400);
    }

    public function __destruct()
    {
        $this->remove($this->root);
    }

    private function remove(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_dir($path) && !is_link($path)) {
            foreach (scandir($path) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    $this->remove($path . '/' . $entry);
                }
            }

            @rmdir($path);

            return;
        }

        @unlink($path);
    }
}
