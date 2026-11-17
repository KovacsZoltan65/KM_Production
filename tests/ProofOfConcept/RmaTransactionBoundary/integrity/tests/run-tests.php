<?php

declare(strict_types=1);

use RmaTransactionBoundaryPoc\Integrity\Tests\ManifestValidationTest;

require dirname(__DIR__).'/src/Manifest.php';
require dirname(__DIR__).'/src/ValidationResult.php';
require dirname(__DIR__).'/src/ManifestValidator.php';
require __DIR__.'/ManifestValidationTest.php';

// Only this standalone test harness reads its own schema; validation performs no I/O.
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$schema = json_decode(file_get_contents(dirname(__DIR__).'/schema/input-manifest-v1.schema.json'), true, 32, JSON_THROW_ON_ERROR);
$failed = 0;
$tests = ManifestValidationTest::cases($schema);
foreach ($tests as $name => $test) {
    try {
        $test();
    } catch (Throwable $error) {
        $failed++;
        fwrite(STDERR, 'FAIL '.$name.': '.$error->getMessage().PHP_EOL);
    }
}
restore_error_handler();
echo count($tests).' tests; '.(count($tests) - $failed).' passed; '.$failed.' failed.'.PHP_EOL;
exit($failed === 0 ? 0 : 1);
