<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use RuntimeException;
use UnexpectedValueException;

/**
 * Pure C08 only. Both methods require the immutable snapshot from successful A1.1
 * validation. Manifest's public constructor is NOT validation evidence. These
 * defensive representation guards do not reimplement schema/path validation.
 */
final class CanonicalJson
{
    private const MAX_CANONICAL_BYTES = 44584955;

    public static function serialize(Manifest $validatedManifest): string
    {
        if (PHP_VERSION_ID < 80400 || PHP_INT_SIZE !== 8) {
            throw new RuntimeException('Canonical JSON requires PHP 8.4+, 64-bit.');
        }
        $m = $validatedManifest;
        if (! array_is_list($m->files) || ! array_is_list($m->historicalArtifacts)
            || $m->files === [] || $m->historicalArtifacts === []
            || count($m->files) + count($m->historicalArtifacts) > 100000) {
            throw new UnexpectedValueException('Expected bounded validated inventories.');
        }
        $out = '{"createdAt":'.self::quote($m->createdAt).',"exampleOnly":'.self::scalar($m->exampleOnly).',"files":[';
        foreach ($m->files as $index => $entry) {
            self::entry($entry, false);
            self::append($out, ($index === 0 ? '' : ',').self::object([
                'category' => $entry['category'], 'path' => $entry['path'],
                'sha256' => $entry['sha256'], 'sizeBytes' => $entry['sizeBytes'],
            ]));
        }
        self::append($out, '],"formatId":'.self::quote($m->formatId).',"historicalArtifacts":[');
        foreach ($m->historicalArtifacts as $index => $entry) {
            self::entry($entry, true);
            self::append($out, ($index === 0 ? '' : ',').self::object([
                'path' => $entry['path'], 'provenanceRef' => $entry['provenanceRef'],
                'sha256' => $entry['sha256'], 'sizeBytes' => $entry['sizeBytes'],
            ]));
        }
        self::keys($m->verificationMetadata, ['baselineCommit', 'profileId', 'profileSha256', 'provenanceRef']);
        foreach ($m->verificationMetadata as $value) {
            if (! is_string($value)) {
                throw new UnexpectedValueException('Expected validated string metadata.');
            }
        }
        self::append($out, '],"manifestId":'.self::quote($m->manifestId).',"pocVersion":'.self::quote($m->pocVersion)
            .',"schemaVersion":'.self::quote($m->schemaVersion).',"verificationMetadata":'.self::object($m->verificationMetadata).'}'."\n");

        return $out;
    }

    /** Caller supplies the same original snapshot used by TD01/TD02; no identity proof here. */
    public static function isCanonical(Manifest $validatedManifest, string $rawBytes): bool
    {
        $canonical = self::serialize($validatedManifest);

        return strlen($canonical) === strlen($rawBytes) && $canonical === $rawBytes;
    }

    private static function entry(mixed $entry, bool $history): void
    {
        if (! is_array($entry)) {
            throw new UnexpectedValueException('Expected a validated entry snapshot.');
        }
        self::keys($entry, $history ? ['path', 'provenanceRef', 'sha256', 'sizeBytes'] : ['category', 'path', 'sha256', 'sizeBytes']);
        foreach ($entry as $key => $value) {
            if ($key !== 'sizeBytes' && ! is_string($value)) {
                throw new UnexpectedValueException('Expected validated entry strings.');
            }
        }
        if (! is_int($entry['sizeBytes']) || $entry['sizeBytes'] < 0 || $entry['sizeBytes'] > 2147483648) {
            throw new UnexpectedValueException('Expected a validated integer file size.');
        }
    }

    private static function keys(array $fields, array $expected): void
    {
        $keys = array_keys($fields);
        sort($keys, SORT_STRING);
        if ($keys !== $expected) {
            throw new UnexpectedValueException('Expected the exact validated object shape.');
        }
    }

    /** Only small fixed-shape objects, never inventory arrays or recursive arbitrary graphs. */
    private static function object(array $fields): string
    {
        ksort($fields, SORT_STRING);
        $parts = [];
        foreach ($fields as $key => $value) {
            $parts[] = self::quote($key).':'.self::scalar($value);
        }

        return '{'.implode(',', $parts).'}';
    }

    private static function scalar(mixed $value): string
    {
        if (is_string($value)) {
            return self::quote($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) && $value >= 0) {
            return (string) $value;
        }
        throw new UnexpectedValueException('Unsupported canonical scalar representation.');
    }

    /** Isolated escape-table support does not widen the A1.1 manifest field domain. */
    private static function quote(string $value): string
    {
        if (strlen($value) > 240) {
            throw new UnexpectedValueException('Expected a bounded validated ASCII string.');
        }
        $out = '"';
        for ($i = 0, $length = strlen($value); $i < $length; $i++) {
            $byte = ord($value[$i]);
            if ($byte > 127) {
                throw new UnexpectedValueException('Expected validated ASCII data.');
            }
            $out .= match ($byte) {
                34 => '\\"', 92 => '\\\\', 8 => '\\b', 9 => '\\t', 10 => '\\n', 12 => '\\f', 13 => '\\r',
                default => $byte < 32 || $byte === 127 ? sprintf('\\u%04x', $byte) : $value[$i],
            };
        }

        return $out.'"';
    }

    private static function append(string &$out, string $part): void
    {
        if (strlen($out) + strlen($part) > self::MAX_CANONICAL_BYTES) {
            throw new UnexpectedValueException('Validated canonical bound exceeded.');
        }
        $out .= $part;
    }
}
