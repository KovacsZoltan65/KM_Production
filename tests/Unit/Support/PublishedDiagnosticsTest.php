<?php

use App\Support\Diagnostics\PublishedDiagnostics;
use Symfony\Component\Process\Process;

it('drops arbitrary sensitive content from both annotations and the published XML', function (string $kind) {
    $sentinel = 'CI_SYNTHETIC_'.bin2hex(random_bytes(16));
    $value = match ($kind) {
        'token' => 'token='.$sentinel,
        'multiline' => "header\n{$sentinel}\r\ntail",
        'special' => "<>&\"'%,:\n{$sentinel}",
        'long' => str_repeat('a', 5000).$sentinel,
        default => $sentinel,
    };
    $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $xml = '<testsuites name="'.$escaped.'"><properties><property value="'.$escaped.'"/></properties><testsuite><testcase name="'.$escaped.'" classname="'.$escaped.'" file="'.$escaped.'" line="7"><failure type="'.$escaped.'" message="'.$escaped.'">'.$escaped.'</failure><system-out>'.$escaped.'</system-out></testcase></testsuite></testsuites>';
    $result = PublishedDiagnostics::junit($xml, dirname(__DIR__, 3));
    $published = $result['xml'].implode('', $result['annotations']);
    expect(str_contains($published, $sentinel))->toBeFalse()
        ->and(strlen($published) < 1200)->toBeTrue()
        ->and(count($result['annotations']))->toBe(1);
    $document = new DOMDocument;
    expect($document->loadXML($result['xml']))->toBeTrue()
        ->and($document->getElementsByTagName('failure')->length)->toBe(1)
        ->and(str_contains($published, 'case-'))->toBeTrue();
})->with(['token', 'multiline', 'special', 'long', 'unrecognized']);

it('keeps existing source references, deterministic identifiers and failure categories', function () {
    $xml = '<testsuite><testcase name="synthetic failure" class="Tests\Unit\Support\PublishedDiagnosticsTest" line="12"><error message="omitted"/></testcase><testcase name="pass"/><testcase name="skip"><skipped/></testcase></testsuite>';
    $result = PublishedDiagnostics::junit($xml, dirname(__DIR__, 3));
    expect(str_contains($result['annotations'][0], 'tests/Unit/Support/PublishedDiagnosticsTest.php,line=12'))->toBeTrue()
        ->and(str_contains($result['annotations'][0], '(error)'))->toBeTrue()
        ->and($result)->toBe(PublishedDiagnostics::junit($xml, dirname(__DIR__, 3)));
});

it('escapes workflow data and properties without introducing command lines', function () {
    expect(PublishedDiagnostics::escape("a%\r\nb:,", true))->toBe('a%25%0D%0Ab%3A%2C')
        ->and(PublishedDiagnostics::escape("a%\r\nb:,"))->toBe('a%25%0D%0Ab:,');
});

it('resolves Pest method-qualified source files without publishing the method name', function () {
    $marker = 'CI_SYNTHETIC_'.bin2hex(random_bytes(16));
    $xml = '<testsuite><testcase name="'.$marker.'" file="tests/Unit/Support/PublishedDiagnosticsTest.php::'.$marker.'"><failure/></testcase></testsuite>';
    $result = PublishedDiagnostics::junit($xml, dirname(__DIR__, 3));
    expect(str_contains($result['annotations'][0], 'file=tests/Unit/Support/PublishedDiagnosticsTest.php,'))->toBeTrue()
        ->and(str_contains($result['xml'].implode('', $result['annotations']), $marker))->toBeFalse();
});

it('rejects malformed XML and document types without echoing their contents', function (string $source) {
    expect(fn () => PublishedDiagnostics::junit($source, dirname(__DIR__, 3)))->toThrow(RuntimeException::class);
})->with(['<broken>', '<!DOCTYPE testsuite SYSTEM "file:///nonexistent"><testsuite/>']);

it('publishes only minimal XML through the actual CLI and fails closed on invalid input', function () {
    $root = dirname(__DIR__, 3);
    $directory = $root.'/storage/framework/testing/ci-diagnostics-'.bin2hex(random_bytes(8));
    mkdir($directory, 0700, true);
    $raw = $directory.'/raw.xml';
    $published = $directory.'/published.xml';
    $marker = 'CI_SYNTHETIC_'.bin2hex(random_bytes(16));
    try {
        file_put_contents($raw, '<testsuite><testcase name="'.$marker.'"><failure message="'.$marker.'"/></testcase></testsuite>');
        $process = new Process([PHP_BINARY, '-d', 'xdebug.mode=off', $root.'/scripts/report-junit-failures.php', $raw, $published], $root);
        $process->run();
        expect($process->getExitCode())->toBe(0);
        $content = $process->getOutput().$process->getErrorOutput().file_get_contents($published);
        expect(str_contains($content, $marker))->toBeFalse()
            ->and(str_contains($content, '::error file=tests'))->toBeTrue();
        file_put_contents($raw, '<invalid');
        $process->run();
        expect($process->getExitCode())->toBe(1)
            ->and(is_file($published))->toBeFalse()
            ->and(str_contains($process->getOutput().$process->getErrorOutput(), '<invalid'))->toBeFalse();
    } finally {
        foreach ([$raw, $published] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($directory);
    }
});

it('preserves Pest exit codes without publishing raw output or exception data', function (string $testFile, int $exitCode, bool $annotated) {
    $root = dirname(__DIR__, 3);
    $reportPath = $root.'/storage/framework/testing/ci-runner-'.bin2hex(random_bytes(8)).'.xml';
    $marker = 'CI_SYNTHETIC_'.bin2hex(random_bytes(16));
    try {
        $process = new Process([
            PHP_BINARY, '-d', 'xdebug.mode=off', $root.'/scripts/run-ci-backend-tests.php',
            'sqlite', $reportPath, $testFile,
        ], $root, ['CI_DIAGNOSTIC_SENTINEL' => $marker]);
        $process->run();
        expect($process->getExitCode())->toBe($exitCode)
            ->and(str_contains($process->getOutput().$process->getErrorOutput(), $marker))->toBeFalse()
            ->and(str_contains($process->getOutput(), '::error file=tests/fixtures/ci-diagnostics/SyntheticFailureTest.php'))->toBe($annotated);
    } finally {
        if (is_file($reportPath)) {
            unlink($reportPath);
        }
    }
})->with([
    'synthetic runtime error' => ['tests/fixtures/ci-diagnostics/SyntheticFailureTest.php', 2, true],
    'successful tests' => ['tests/Unit/Support/TestDiagnosticsTest.php', 0, false],
]);
