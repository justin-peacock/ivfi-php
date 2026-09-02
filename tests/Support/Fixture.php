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

        copy(__DIR__ . '/../../build/indexer.php', $this->root . '/indexer.php');
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
        file_put_contents(
            $this->root . '/indexer.config.php',
            "<?php\nreturn " . var_export($config, true) . ";\n"
        );
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
