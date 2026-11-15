<?php

declare(strict_types=1);

use App\Support\Diagnostics\PublishedDiagnostics;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

$engine = $argv[1] ?? '';
$reportPath = $argv[2] ?? '';
if (! in_array($engine, ['sqlite', 'mysql'], true) || $reportPath === '') {
    fwrite(STDERR, 'Invalid CI test runner arguments.'.PHP_EOL);
    exit(64);
}

$log = null;
try {
    $directory = dirname(__DIR__).'/storage/framework/testing';
    if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
        throw new RuntimeException('Private diagnostics directory unavailable.');
    }
    $log = @fopen($directory.'/ci-backend-'.$engine.'.log', 'wb');
    if ($log === false) {
        throw new RuntimeException('Private diagnostics unavailable.');
    }
    $process = new Process([
        PHP_BINARY,
        '-d', 'xdebug.mode=off',
        __DIR__.'/backend-test-environment.php',
        $engine, 'test', '--log-junit='.$reportPath,
        ...array_slice($argv, 3),
    ], dirname(__DIR__));
    $process->setTimeout(null);
    $exitCode = $process->run(static function (string $type, string $buffer) use ($log): void {
        fwrite($log, $buffer);
    });
    fwrite(STDOUT, "CI backend test result: {$engine}, exit {$exitCode}; raw output retained privately.".PHP_EOL);
    if ($exitCode !== 0 && is_file($reportPath)) {
        $report = PublishedDiagnostics::junit((string) @file_get_contents($reportPath), dirname(__DIR__));
        foreach ($report['annotations'] as $annotation) {
            fwrite(STDOUT, $annotation.PHP_EOL);
        }
    }
} catch (Throwable) {
    fwrite(STDERR, 'CI backend test execution failed; raw details omitted.'.PHP_EOL);
    $exitCode = 1;
} finally {
    if (is_resource($log)) {
        fclose($log);
    }
}
exit($exitCode);
