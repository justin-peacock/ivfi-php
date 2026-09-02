<?php

declare(strict_types=1);

/**
 * The unit under test is the built `indexer.php`, not the webpack template,
 * so make sure a build exists before any test runs.
 */
$build = __DIR__ . '/../build/indexer.php';

if (!file_exists($build)) {
    fwrite(STDERR, sprintf(
        "No build found at %s.\nRun `npm run build` before the PHP test suite.\n",
        $build
    ));

    exit(1);
}

require __DIR__ . '/Support/Fixture.php';
require __DIR__ . '/Support/Indexer.php';
require __DIR__ . '/Support/Server.php';
require __DIR__ . '/Support/IndexerTestCase.php';
