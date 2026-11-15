<?php

declare(strict_types=1);

namespace App\Support\Diagnostics;

use DOMDocument;
use DOMElement;
use RuntimeException;

/** Publish allowlisted metadata instead of trying to recognize arbitrary secrets. */
final class PublishedDiagnostics
{
    /** @return array{xml: string, annotations: list<string>} */
    public static function junit(string $source, string $projectRoot): array
    {
        if (stripos($source, '<!DOCTYPE') !== false) {
            throw new RuntimeException('JUnit report contains an unsupported document type.');
        }

        $previous = libxml_use_internal_errors(true);
        $input = new DOMDocument;
        try {
            $loaded = $input->loadXML($source, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded || ! in_array($input->documentElement?->tagName, ['testsuites', 'testsuite'], true)) {
            throw new RuntimeException('JUnit report is invalid.');
        }

        $output = new DOMDocument('1.0', 'UTF-8');
        $output->formatOutput = true;
        $suite = $output->createElement('testsuite');
        $suite->setAttribute('name', 'CI tests');
        $output->appendChild($suite);
        $counts = ['tests' => 0, 'failures' => 0, 'errors' => 0, 'skipped' => 0];
        $annotations = [];

        foreach ($input->getElementsByTagName('testcase') as $case) {
            $counts['tests']++;
            $id = 'case-'.substr(hash('sha256', $case->getAttribute('classname')."\0".$case->getAttribute('class')."\0".$case->getAttribute('name')), 0, 24);
            $file = self::sourceFile($case, $projectRoot);
            $line = max(1, min(1_000_000, (int) $case->getAttribute('line')));
            $published = $output->createElement('testcase');
            $published->setAttribute('name', $id);
            $published->setAttribute('classname', 'CI');
            $published->setAttribute('file', $file);
            $published->setAttribute('line', (string) $line);
            $suite->appendChild($published);

            foreach (['error' => 'errors', 'failure' => 'failures', 'skipped' => 'skipped'] as $category => $counter) {
                if ($case->getElementsByTagName($category)->length === 0) {
                    continue;
                }

                $counts[$counter]++;
                $message = "Test {$category}; raw diagnostic data omitted.";
                $detail = $output->createElement($category);
                $detail->setAttribute('type', 'CI'.ucfirst($category));
                $detail->setAttribute('message', $message);
                $published->appendChild($detail);

                if ($category !== 'skipped' && count($annotations) < 10) {
                    $annotations[] = sprintf(
                        '::error file=%s,line=%d,title=%s::%s',
                        self::escape($file, true), $line,
                        self::escape("{$id} ({$category})", true), self::escape($message),
                    );
                }
                break;
            }
        }

        foreach ($counts as $name => $count) {
            $suite->setAttribute($name, (string) $count);
        }

        return ['xml' => (string) $output->saveXML(), 'annotations' => $annotations];
    }

    public static function escape(string $value, bool $property = false): string
    {
        $value = str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $value);

        return $property ? str_replace([':', ','], ['%3A', '%2C'], $value) : $value;
    }

    private static function sourceFile(DOMElement $case, string $projectRoot): string
    {
        $class = $case->getAttribute('class') ?: $case->getAttribute('classname');
        // Pest appends "::test name" to testcase files; the suite keeps the file itself.
        $candidates = [explode('::', $case->getAttribute('file'), 2)[0]];
        if ($case->parentNode instanceof DOMElement) {
            $candidates[] = $case->parentNode->getAttribute('file');
        }
        if (str_starts_with($class, 'Tests\\')) {
            $candidates[] = lcfirst(str_replace('\\', '/', $class)).'.php';
        }

        $root = str_replace('\\', '/', (string) realpath($projectRoot));
        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $path = realpath($candidate) ?: realpath($projectRoot.'/'.$candidate);
            $path = str_replace('\\', '/', (string) $path);
            if (! str_starts_with($path, $root.'/tests/')) {
                continue;
            }
            $relative = substr($path, strlen($root) + 1);
            if (preg_match('~^tests/[A-Za-z0-9_./-]+\.(php|js|ts)$~D', $relative) === 1) {
                return $relative;
            }
        }

        return 'tests';
    }
}
