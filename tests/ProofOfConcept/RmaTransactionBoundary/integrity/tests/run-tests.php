<?php

declare(strict_types=1);

use RmaTransactionBoundaryPoc\Integrity\Tests\ManifestValidationTest;
use RmaTransactionBoundaryPoc\Integrity\Tests\StrictJsonTest;
use RmaTransactionBoundaryPoc\Integrity\Tests\TrustVerificationTest;

$suite = $argv[1] ?? '--all';
$memoryCase = str_starts_with($suite, '--td02-memory-case=') ? substr($suite, strlen('--td02-memory-case=')) : null;
if (count($argv) > 2 || (! in_array($suite, ['--all', '--a11', '--td01', '--td02', '--td02-memory'], true)
    && ! in_array($memoryCase, ['wire-max', 'byte-heavy', 'object-heavy', 'array-heavy', 'invalid-stress', 'defensive-limit'], true))) {
    fwrite(STDERR, 'Usage: php -n integrity/tests/run-tests.php [--all|--a11|--td01|--td02|--td02-memory|--td02-memory-case=ID]'.PHP_EOL);
    exit(64);
}
if (PHP_VERSION_ID < 80400 || PHP_INT_SIZE !== 8) {
    fwrite(STDERR, 'Standalone integrity tests require PHP 8.4+, 64-bit.'.PHP_EOL);
    exit(2);
}

set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$groups = [];
if (in_array($suite, ['--all', '--a11', '--td02-memory'], true) || $memoryCase !== null) {
    require dirname(__DIR__).'/src/Manifest.php';
    require dirname(__DIR__).'/src/ValidationResult.php';
    require dirname(__DIR__).'/src/ManifestValidator.php';
    require __DIR__.'/ManifestValidationTest.php';
    // Only this standalone test harness reads its own schema; validation performs no I/O.
    if (in_array($suite, ['--all', '--a11'], true)) {
        $schema = json_decode(file_get_contents(dirname(__DIR__).'/schema/input-manifest-v1.schema.json'), true, 32, JSON_THROW_ON_ERROR);
        $groups['A1.1'] = ManifestValidationTest::cases($schema);
    }
}
if (in_array($suite, ['--all', '--td01'], true)) {
    require dirname(__DIR__).'/src/TrustAnchor.php';
    require dirname(__DIR__).'/src/TrustContext.php';
    require dirname(__DIR__).'/src/AnchorProvider.php';
    require dirname(__DIR__).'/src/AnchorValidator.php';
    require dirname(__DIR__).'/src/DigestComparisonResult.php';
    require dirname(__DIR__).'/src/TrustedDigestVerifier.php';
    require __DIR__.'/TrustVerificationTest.php';
    $groups['TD01'] = TrustVerificationTest::cases();
}
if (in_array($suite, ['--all', '--td02', '--td02-memory'], true) || $memoryCase !== null) {
    require dirname(__DIR__).'/src/JsonParseResult.php';
    require dirname(__DIR__).'/src/StrictJsonTokenizer.php';
    require __DIR__.'/StrictJsonTest.php';
    if ($memoryCase !== null) {
        echo json_encode(StrictJsonTest::memoryCase($memoryCase), JSON_THROW_ON_ERROR).PHP_EOL;
        exit(0);
    }
    if ($suite === '--td02-memory') {
        exit(StrictJsonTest::memorySuite(__FILE__));
    }
    $groups['TD02'] = StrictJsonTest::cases();
}

$failed = 0;
$total = 0;
foreach ($groups as $group => $tests) {
    $groupFailed = 0;
    $observeA11 = $group === 'A1.1' && function_exists('xdebug_start_function_monitor')
        && in_array('develop', xdebug_info('mode'), true);
    if ($observeA11) {
        xdebug_start_function_monitor([ManifestValidationTest::class.'::ensure', ManifestValidationTest::class.'::expectError']);
        $eventStart = count(xdebug_get_monitored_functions());
    }
    foreach ($tests as $name => $test) {
        try {
            $test();
        } catch (Throwable $error) {
            $failed++;
            $groupFailed++;
            fwrite(STDERR, 'FAIL '.$name.': '.$error->getMessage().PHP_EOL);
        }
    }
    $total += count($tests);
    echo $group.': '.count($tests).' tests; '.(count($tests) - $groupFailed).' passed; '.$groupFailed.' failed.'.PHP_EOL;
    if ($observeA11) {
        $counts = ['ensure' => 0, 'expectError' => 0];
        foreach (array_slice(xdebug_get_monitored_functions(), $eventStart) as $event) {
            foreach (array_keys($counts) as $method) {
                if ($event['function'] === ManifestValidationTest::class.'::'.$method) {
                    $counts[$method]++;
                }
            }
        }
        xdebug_stop_function_monitor();
        echo 'A1.1 observed assertions: '.array_sum($counts).' (ensure='.$counts['ensure'].', expected-error checks='.$counts['expectError'].').'.PHP_EOL;
    }
}
if (isset($groups['TD01'])) {
    echo 'TD01: '.TrustVerificationTest::$assertions.' assertions; '.TrustVerificationTest::$resultsChecked.' results checked for scope invariants.'.PHP_EOL;
}
if (isset($groups['TD02'])) {
    echo 'TD02: '.StrictJsonTest::$assertions.' assertions.'.PHP_EOL;
}
restore_error_handler();
echo $total.' tests; '.($total - $failed).' passed; '.$failed.' failed.'.PHP_EOL;
exit($failed === 0 ? 0 : 1);
