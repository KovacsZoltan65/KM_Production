<?php

declare(strict_types=1);

if ($argc !== 2) {
    fwrite(STDERR, "Használat: php scripts/report-junit-failures.php <junit.xml>\n");

    exit(2);
}

$reportPath = $argv[1];

if (! is_file($reportPath)) {
    fwrite(STDERR, "A JUnit riport nem található: {$reportPath}\n");

    exit(1);
}

libxml_use_internal_errors(true);
$report = simplexml_load_file($reportPath);

if ($report === false) {
    fwrite(STDERR, "A JUnit riport nem olvasható: {$reportPath}\n");

    exit(1);
}

$failedCases = $report->xpath('//testcase[failure or error]') ?: [];

if ($failedCases === []) {
    fwrite(STDOUT, "A JUnit riport nem tartalmaz annotálható teszthibát.\n");

    exit(0);
}

$escapeMessage = static fn (string $value): string => str_replace(
    ['%', "\r", "\n"],
    ['%25', '%0D', '%0A'],
    $value,
);
$escapeProperty = static fn (string $value): string => str_replace(
    ['%', "\r", "\n", ':', ','],
    ['%25', '%0D', '%0A', '%3A', '%2C'],
    $value,
);

foreach (array_slice($failedCases, 0, 10) as $testCase) {
    $attributes = $testCase->attributes();
    $failure = isset($testCase->failure[0]) ? $testCase->failure[0] : $testCase->error[0];
    $failureAttributes = $failure->attributes();
    $testName = trim((string) ($attributes['name'] ?? 'Ismeretlen teszt'));
    $className = trim((string) ($attributes['class'] ?? $attributes['classname'] ?? ''));
    $title = $className === '' ? $testName : "{$className} — {$testName}";
    $message = trim((string) ($failureAttributes['message'] ?? $failure));
    $file = str_starts_with($className, 'Tests\\')
        ? lcfirst(str_replace('\\', '/', $className)).'.php'
        : 'tests';
    $line = max(1, (int) ($attributes['line'] ?? 1));

    if ($message === '') {
        $message = 'A teszt hibával állt le; részletek a JUnit artifactban.';
    }

    if (strlen($message) > 3000) {
        $message = substr($message, 0, 3000).'…';
    }

    fwrite(
        STDOUT,
        sprintf(
            "::error file=%s,line=%d,title=%s::%s\n",
            $escapeProperty($file),
            $line,
            $escapeProperty($title),
            $escapeMessage($message),
        ),
    );
}

if (count($failedCases) > 10) {
    fwrite(
        STDOUT,
        sprintf(
            "::warning title=JUnit összesítés::További %d teszthiba a JUnit artifactban található.\n",
            count($failedCases) - 10,
        ),
    );
}
