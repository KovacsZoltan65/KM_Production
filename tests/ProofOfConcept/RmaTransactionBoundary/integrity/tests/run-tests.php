<?php

declare(strict_types=1);

use RmaTransactionBoundaryPoc\Integrity\Tests\ManifestValidationTest;
use RmaTransactionBoundaryPoc\Integrity\Tests\TrustVerificationTest;

$suite = $argv[1] ?? '--all';
if (count($argv) > 2 || ! in_array($suite, ['--all', '--a11', '--td01'], true)) {
    fwrite(STDERR, 'Usage: php -n integrity/tests/run-tests.php [--all|--a11|--td01]'.PHP_EOL);
    exit(64);
}

set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$groups = [];
if ($suite !== '--td01') {
    require dirname(__DIR__).'/src/Manifest.php';
    require dirname(__DIR__).'/src/ValidationResult.php';
    require dirname(__DIR__).'/src/ManifestValidator.php';
    require __DIR__.'/ManifestValidationTest.php';
    // Only this standalone test harness reads its own schema; validation performs no I/O.
    $schema = json_decode(file_get_contents(dirname(__DIR__).'/schema/input-manifest-v1.schema.json'), true, 32, JSON_THROW_ON_ERROR);
    $groups['A1.1'] = ManifestValidationTest::cases($schema);
}
if ($suite !== '--a11') {
    require dirname(__DIR__).'/src/TrustAnchor.php';
    require dirname(__DIR__).'/src/TrustContext.php';
    require dirname(__DIR__).'/src/AnchorProvider.php';
    require dirname(__DIR__).'/src/AnchorValidator.php';
    require dirname(__DIR__).'/src/DigestComparisonResult.php';
    require dirname(__DIR__).'/src/TrustedDigestVerifier.php';
    require __DIR__.'/TrustVerificationTest.php';
    $groups['TD01'] = TrustVerificationTest::cases();
}

$failed = 0;
$total = 0;
foreach ($groups as $group => $tests) {
    $groupFailed = 0;
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
}
if ($suite !== '--a11') {
    echo 'TD01: '.TrustVerificationTest::$assertions.' assertions; '.TrustVerificationTest::$resultsChecked.' results checked for scope invariants.'.PHP_EOL;
}
restore_error_handler();
echo $total.' tests; '.($total - $failed).' passed; '.$failed.' failed.'.PHP_EOL;
exit($failed === 0 ? 0 : 1);
