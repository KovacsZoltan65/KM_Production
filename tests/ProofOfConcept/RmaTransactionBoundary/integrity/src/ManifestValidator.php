<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use stdClass;

final class ManifestValidator
{
    private const ROOT_FIELDS = [
        'formatId', 'schemaVersion', 'manifestId', 'pocVersion', 'createdAt',
        'exampleOnly', 'files', 'historicalArtifacts', 'verificationMetadata',
    ];

    private const METADATA_FIELDS = ['baselineCommit', 'profileId', 'profileSha256', 'provenanceRef'];

    private const CATEGORIES = ['poc_code', 'schema', 'toolchain', 'dependency', 'configuration', 'documentation'];

    private const MAX_ENTRIES = 100000;

    private const MAX_FILE_BYTES = 2147483648;

    private const MAX_TOTAL_BYTES = 8589934592;

    private const MAX_VALIDATION_ERRORS = 100;

    public function validate(mixed $manifestData): ValidationResult
    {
        $errors = [];
        try {
            return $this->validateData($manifestData, $errors);
        } catch (ValidationErrorBudgetReached) {
            return ValidationResult::failure($errors, diagnosticsTruncated: true);
        }
    }

    /** @param list<array{code: string, path: string, message: string}> $errors */
    private function validateData(mixed $manifestData, array &$errors): ValidationResult
    {
        $root = $this->objectFields($manifestData, '$', self::ROOT_FIELDS, $errors);
        if ($root === null) {
            return ValidationResult::failure($errors);
        }

        $this->constantString($root, 'formatId', 'rma-poc-inputs', 'FORMAT_UNSUPPORTED', '$', $errors);
        $this->constantString($root, 'schemaVersion', '1.0', 'SCHEMA_UNSUPPORTED', '$', $errors);
        foreach (['manifestId', 'pocVersion'] as $key) {
            $this->patternString($root, $key, '/\A[A-Za-z0-9._-]{1,80}\z/', 'IDENTIFIER_INVALID', '$', $errors);
        }

        $timestamp = $this->stringField($root, 'createdAt', '$', $errors);
        if ($timestamp !== null && ! $this->validTimestamp($timestamp)) {
            $this->error($errors, 'TIMESTAMP_INVALID', '$.createdAt', 'Expected a valid Gregorian UTC timestamp in YYYY-MM-DDTHH:mm:ssZ format.');
        }

        if (array_key_exists('exampleOnly', $root)) {
            if (! is_bool($root['exampleOnly'])) {
                $this->error($errors, 'TYPE_INVALID', '$.exampleOnly', 'Expected a boolean.');
            } elseif ($root['exampleOnly']) {
                $this->error($errors, 'EXAMPLE_ONLY', '$.exampleOnly', 'Illustrative manifests are not accepted as input manifests.');
            }
        }

        $metadata = null;
        if (array_key_exists('verificationMetadata', $root)) {
            $metadata = $this->objectFields($root['verificationMetadata'], '$.verificationMetadata', self::METADATA_FIELDS, $errors);
            if ($metadata !== null) {
                foreach (['profileId', 'provenanceRef'] as $key) {
                    $this->patternString($metadata, $key, '/\A[A-Za-z0-9._-]{1,80}\z/', 'IDENTIFIER_INVALID', '$.verificationMetadata', $errors);
                }
                $this->patternString($metadata, 'baselineCommit', '/\A[0-9a-f]{40}\z/', 'HASH_INVALID', '$.verificationMetadata', $errors);
                $this->patternString($metadata, 'profileSha256', '/\A[0-9a-f]{64}\z/', 'HASH_INVALID', '$.verificationMetadata', $errors);
            }
        }

        $files = $this->inventory($root, 'files', $errors);
        $history = $this->inventory($root, 'historicalArtifacts', $errors);
        if (count($files) + count($history) > self::MAX_ENTRIES) {
            $this->error($errors, 'LIMIT_EXCEEDED', '$', 'The combined inventories exceed 100000 entries.');

            return ValidationResult::failure($errors);
        }

        $seenPaths = [];
        $seenFoldedPaths = [];
        $remainingBytes = self::MAX_TOTAL_BYTES;
        foreach (['files' => $files, 'historicalArtifacts' => $history] as $field => $entries) {
            $previousPath = null;
            foreach ($entries as $index => $value) {
                $location = '$.'.$field.'['.$index.']';
                $fields = ['path', 'sizeBytes', 'sha256', $field === 'files' ? 'category' : 'provenanceRef'];
                $entry = $this->objectFields($value, $location, $fields, $errors);
                if ($entry === null) {
                    continue;
                }

                $path = $this->stringField($entry, 'path', $location, $errors);
                if ($path !== null) {
                    if (! $this->validPath($path)) {
                        $this->error($errors, 'PATH_INVALID', $location.'.path', 'Expected a canonical ASCII relative path without Windows device names or dot segments.');
                    } else {
                        if ($previousPath !== null && strcmp($previousPath, $path) > 0) {
                            $this->error($errors, 'INVENTORY_ORDER_INVALID', $location.'.path', 'Inventory paths must be in ascending ASCII byte order.');
                        }
                        $previousPath = $path;
                        $folded = strtr($path, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
                        if (isset($seenPaths[$path])) {
                            $this->error($errors, 'DUPLICATE_PATH', $location.'.path', 'A path may appear only once across both inventories.');
                        } elseif (isset($seenFoldedPaths[$folded])) {
                            $this->error($errors, 'CASE_COLLISION', $location.'.path', 'Paths must not collide under ASCII case-insensitive comparison.');
                        }
                        $seenPaths[$path] = true;
                        $seenFoldedPaths[$folded] = true;
                    }
                }

                if (array_key_exists('sizeBytes', $entry)) {
                    if (! is_int($entry['sizeBytes'])) {
                        $this->error($errors, 'TYPE_INVALID', $location.'.sizeBytes', 'Expected a PHP integer, without coercion.');
                    } elseif ($entry['sizeBytes'] < 0 || $entry['sizeBytes'] > self::MAX_FILE_BYTES) {
                        $this->error($errors, 'SIZE_INVALID', $location.'.sizeBytes', 'File size must be between 0 and 2147483648 bytes.');
                    } elseif ($remainingBytes !== null) {
                        if ($entry['sizeBytes'] > $remainingBytes) {
                            $this->error($errors, 'LIMIT_EXCEEDED', '$', 'The combined file sizes exceed 8589934592 bytes.');
                            $remainingBytes = null;
                        } else {
                            $remainingBytes -= $entry['sizeBytes'];
                        }
                    }
                }
                $this->patternString($entry, 'sha256', '/\A[0-9a-f]{64}\z/', 'HASH_INVALID', $location, $errors);
                if ($field === 'files') {
                    $category = $this->stringField($entry, 'category', $location, $errors);
                    if ($category !== null && ! in_array($category, self::CATEGORIES, true)) {
                        $this->error($errors, 'CATEGORY_INVALID', $location.'.category', 'Unknown file category.');
                    }
                } else {
                    $this->patternString($entry, 'provenanceRef', '/\A[A-Za-z0-9._-]{1,80}\z/', 'IDENTIFIER_INVALID', $location, $errors);
                }
            }
        }

        if ($errors !== []) {
            return ValidationResult::failure($errors);
        }

        return ValidationResult::success(new Manifest(
            $root['formatId'], $root['schemaVersion'], $root['manifestId'], $root['pocVersion'],
            $root['createdAt'], $root['exampleOnly'],
            array_map(static fn (stdClass $entry): array => [
                'path' => $entry->path, 'sizeBytes' => $entry->sizeBytes,
                'sha256' => $entry->sha256, 'category' => $entry->category,
            ], $files),
            array_map(static fn (stdClass $entry): array => [
                'path' => $entry->path, 'sizeBytes' => $entry->sizeBytes,
                'sha256' => $entry->sha256, 'provenanceRef' => $entry->provenanceRef,
            ], $history),
            [
                'baselineCommit' => $metadata['baselineCommit'], 'profileId' => $metadata['profileId'],
                'profileSha256' => $metadata['profileSha256'], 'provenanceRef' => $metadata['provenanceRef'],
            ],
        ));
    }

    /**
     * @param  list<string>  $fields
     * @param  list<array{code: string, path: string, message: string}>  $errors
     * @return array<string, mixed>|null
     */
    private function objectFields(mixed $value, string $location, array $fields, array &$errors): ?array
    {
        if (! $value instanceof stdClass || $value::class !== stdClass::class) {
            $this->error($errors, 'TYPE_INVALID', $location, 'Expected a decoded JSON object (stdClass).');

            return null;
        }

        $data = get_object_vars($value);
        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                $this->error($errors, 'FIELD_REQUIRED', $location.'.'.$field, 'Required field is missing.');
            }
        }
        $unknown = array_diff(array_keys($data), $fields);
        sort($unknown, SORT_STRING);
        foreach (array_values($unknown) as $index => $field) {
            // Do not reflect arbitrary input keys (possibly secrets/absolute paths) into diagnostics.
            $suffix = preg_match('/\A[A-Za-z_][A-Za-z0-9_]{0,79}\z/', (string) $field) === 1
                ? '.'.$field : '.{unknown:'.$index.'}';
            $this->error($errors, 'FIELD_UNKNOWN', $location.$suffix, 'Unknown field is not allowed.');
        }

        return $data;
    }

    private function stringField(array $data, string $key, string $location, array &$errors): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }
        if (! is_string($data[$key])) {
            $this->error($errors, 'TYPE_INVALID', $location.'.'.$key, 'Expected a string, without coercion.');

            return null;
        }

        return $data[$key];
    }

    private function patternString(array $data, string $key, string $pattern, string $code, string $location, array &$errors): void
    {
        $value = $this->stringField($data, $key, $location, $errors);
        if ($value !== null && preg_match($pattern, $value) !== 1) {
            $this->error($errors, $code, $location.'.'.$key, 'String does not match the required ASCII format.');
        }
    }

    private function constantString(array $data, string $key, string $expected, string $code, string $location, array &$errors): void
    {
        $value = $this->stringField($data, $key, $location, $errors);
        if ($value !== null && $value !== $expected) {
            $this->error($errors, $code, $location.'.'.$key, 'Unsupported contract value.');
        }
    }

    /** @return list<mixed> */
    private function inventory(array $root, string $key, array &$errors): array
    {
        if (! array_key_exists($key, $root)) {
            return [];
        }
        $value = $root[$key];
        if (! is_array($value) || ! array_is_list($value)) {
            $this->error($errors, 'TYPE_INVALID', '$.'.$key, 'Expected a JSON array with consecutive integer indices.');

            return [];
        }
        if ($value === []) {
            $this->error($errors, 'INVENTORY_EMPTY', '$.'.$key, 'Inventory must contain at least one entry.');
        } elseif (count($value) > self::MAX_ENTRIES) {
            $this->error($errors, 'LIMIT_EXCEEDED', '$.'.$key, 'Inventory exceeds 100000 entries.');

            return [];
        }

        return $value;
    }

    private function validTimestamp(string $value): bool
    {
        if (preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})Z\z/', $value, $parts) !== 1) {
            return false;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
            && (int) $parts[4] <= 23 && (int) $parts[5] <= 59 && (int) $parts[6] <= 59;
    }

    private function validPath(string $path): bool
    {
        if (strlen($path) > 240 || preg_match('/\A[A-Za-z0-9._-]+(?:\/[A-Za-z0-9._-]+)*\z/', $path) !== 1) {
            return false;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '.' || $segment === '..' || str_ends_with($segment, '.')
                || preg_match('/\A(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|\z)/i', $segment) === 1) {
                return false;
            }
        }

        return true;
    }

    private function error(array &$errors, string $code, string $path, string $message): void
    {
        $errors[] = ['code' => $code, 'path' => $path, 'message' => $message];
        if (count($errors) === self::MAX_VALIDATION_ERRORS) {
            throw new ValidationErrorBudgetReached;
        }
    }
}

/** @internal Control-flow signal caught only at the validator boundary. */
final class ValidationErrorBudgetReached extends \RuntimeException {}
