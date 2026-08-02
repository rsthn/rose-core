<?php
/**
 * Expression-level test runner.
 *
 * Boots rose-core from *this repository's* autoloader, so the .fn suite exercises the working
 * tree. Running the suite through `rose test` instead loads rose-core from rose-cli's vendor
 * directory, which means it validates the last published release and not local changes.
 *
 * Usage:
 *   php test/run-fn.php                    # runs test/test-all.fn
 *   php test/run-fn.php test/test-math.fn  # runs a single file
 */

require(__DIR__.'/../vendor/autoload.php');

use Rose\Main;
use Rose\Expr;
use Rose\IO\Path;

Main::$CORE_DIR = './test';
Main::cli(dirname(__DIR__), true);

$target = $argv[1] ?? 'test/test-all.fn';

if (!Path::exists($target) && Path::exists($target.'.fn'))
    $target .= '.fn';

if (!Path::isFile($target)) {
    echo "\x1B[91mInput not found:\x1B[0m ".$target."\n";
    exit(1);
}

// Load the assertion helpers, then the test definitions themselves.
Expr::eval('(include "test/lib/expect.fn")', null, 'text');
Expr::eval('(include '.json_encode(Path::normalize($target)).')', null, 'text');

$failed = Expr::eval('(test:run)', null, 'arg');

if ($failed) {
    echo "\n\x1B[95mError:\x1B[0m 💀 ".$failed." test".($failed > 1 ? 's' : '')." failed\n";
    exit(1);
}

echo "\n\x1B[92m✔ All tests passed\x1B[0m\n";
exit(0);
