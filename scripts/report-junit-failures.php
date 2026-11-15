<?php

declare(strict_types=1);

use App\Support\Diagnostics\PublishedDiagnostics;

require dirname(__DIR__).'/vendor/autoload.php';

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php scripts/report-junit-failures.php <raw-junit.xml> <published-junit.xml>\n");
    exit(2);
}

try {
    if (realpath($argv[1]) !== false && realpath($argv[1]) === realpath($argv[2])) {
        throw new RuntimeException('Invalid report paths.');
    }
    if (is_file($argv[2]) && ! @unlink($argv[2])) {
        throw new RuntimeException('Previous report could not be removed.');
    }
    if (! is_file($argv[1])) {
        throw new RuntimeException('Report unavailable.');
    }
    $source = @file_get_contents($argv[1]);
    if ($source === false) {
        throw new RuntimeException('Report unavailable.');
    }
    $report = PublishedDiagnostics::junit($source, dirname(__DIR__));
    $directory = dirname($argv[2]);
    if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
        throw new RuntimeException('Report directory unavailable.');
    }
    if (@file_put_contents($argv[2], $report['xml']) === false) {
        throw new RuntimeException('Report could not be published.');
    }
    foreach ($report['annotations'] as $annotation) {
        fwrite(STDOUT, $annotation.PHP_EOL);
    }
    fwrite(STDOUT, 'Minimal JUnit report published; raw diagnostics excluded.'.PHP_EOL);
} catch (Throwable) {
    fwrite(STDERR, 'JUnit publication failed; raw report will not be published.'.PHP_EOL);
    exit(1);
}
