<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity\Tests;

use RmaTransactionBoundaryPoc\Integrity\ManifestValidator;
use RmaTransactionBoundaryPoc\Integrity\ValidationResult;
use RuntimeException;
use stdClass;

final class ManifestValidationTest
{
    /** @return array<string, \Closure(): void> */
    public static function cases(array $schema): array
    {
        $cases = [];
        $invalid = static function (\Closure $change, string $code, string $path): void {
            $data = self::manifest();
            $change($data);
            self::expectError((new ManifestValidator)->validate($data), $code, $path);
        };

        $cases['minimal manifest and immutable DTO'] = static function (): void {
            $result = (new ManifestValidator)->validate(self::manifest());
            self::ensure($result->valid && $result->errors === [] && $result->manifest !== null);
            self::ensure($result->manifest->schemaVersion === '1.0');
            self::ensure($result->manifest->files[0]['sizeBytes'] === 0);
            self::ensure($result->toArray() === ['valid' => true, 'errors' => []]);
            $data = self::manifest();
            $snapshot = (new ManifestValidator)->validate($data)->manifest;
            $data->files[0]->path = 'changed.txt';
            self::ensure($snapshot->files[0]['path'] === 'payload/readme.txt');
        };

        foreach ([null, [], true, 42, 'manifest', new class {}] as $index => $value) {
            $cases['malformed root '.$index] = static function () use ($value): void {
                self::expectError((new ManifestValidator)->validate($value), 'TYPE_INVALID', '$');
            };
        }
        foreach ($schema['required'] as $key) {
            $cases['required root '.$key] = static fn () => $invalid(static function ($data) use ($key): void {
                unset($data->{$key});
            }, 'FIELD_REQUIRED', '$.'.$key);
            $cases['root type '.$key] = static fn () => $invalid(static function ($data) use ($key): void {
                $data->{$key} = null;
            }, 'TYPE_INVALID', '$.'.$key);
        }

        $cases['unsupported version'] = static fn () => $invalid(static function ($data): void {
            $data->schemaVersion = '2.0';
        }, 'SCHEMA_UNSUPPORTED', '$.schemaVersion');
        $cases['unsupported format'] = static fn () => $invalid(static function ($data): void {
            $data->formatId = 'legacy';
        }, 'FORMAT_UNSUPPORTED', '$.formatId');
        $cases['unknown root field'] = static fn () => $invalid(static function ($data): void {
            $data->extra = true;
        }, 'FIELD_UNKNOWN', '$.extra');
        $cases['unsafe unknown key is not reflected'] = static function (): void {
            $data = self::manifest();
            $data->{'C:\private\secret'} = 'sensitive';
            $result = (new ManifestValidator)->validate($data);
            self::expectError($result, 'FIELD_UNKNOWN', '$.{unknown:0}');
            self::ensure(! str_contains(json_encode($result->toArray(), JSON_THROW_ON_ERROR), 'private'));
        };
        $cases['example manifests rejected'] = static fn () => $invalid(static function ($data): void {
            $data->exampleOnly = true;
        }, 'EXAMPLE_ONLY', '$.exampleOnly');

        foreach (['', 'has space', 'é', "bad\n", str_repeat('a', 81)] as $index => $identifier) {
            foreach (['manifestId', 'pocVersion'] as $key) {
                $cases['invalid identifier '.$key.' '.$index] = static fn () => $invalid(static function ($data) use ($key, $identifier): void {
                    $data->{$key} = $identifier;
                }, 'IDENTIFIER_INVALID', '$.'.$key);
            }
        }
        $cases['identifier length 80 accepted'] = static function (): void {
            $data = self::manifest();
            $data->manifestId = str_repeat('a', 80);
            self::ensure((new ManifestValidator)->validate($data)->valid);
        };

        $timestamps = [
            '2026-11-17T12:00:00Z' => true,
            '2026-11-17T12:00:00.123Z' => false,
            '2026-11-17T13:00:00+01:00' => false,
            '2026-11-17T12:00:00+00:00' => false,
            '2026-02-30T12:00:00Z' => false,
            '2026-11-17 12:00:00Z' => false,
            '2026-11-17t12:00:00Z' => false,
            '2026-11-17T12:00:00z' => false,
            '2026-11-17T24:00:00Z' => false,
            '2026-11-17T12:60:00Z' => false,
            '2026-11-17T12:00:60Z' => false,
            '2026-00-17T12:00:00Z' => false,
            '2026-13-17T12:00:00Z' => false,
            '2026-11-00T12:00:00Z' => false,
            '2026-04-31T12:00:00Z' => false,
            '1900-02-29T00:00:00Z' => false,
            '2000-02-29T00:00:00Z' => true,
            '2024-02-29T23:59:59Z' => true,
            '2100-02-29T00:00:00Z' => false,
            '0000-01-01T00:00:00Z' => false,
            '0001-01-01T00:00:00Z' => true,
            '9999-12-31T23:59:59Z' => true,
            "2026-11-17T12:00:00Z\n" => false,
        ];
        foreach ($timestamps as $timestamp => $valid) {
            $cases['timestamp '.str_replace("\n", '\\n', $timestamp)] = static function () use ($timestamp, $valid): void {
                $data = self::manifest();
                $data->createdAt = $timestamp;
                $result = (new ManifestValidator)->validate($data);
                self::ensure($result->valid === $valid);
                if (! $valid) {
                    self::expectError($result, 'TIMESTAMP_INVALID', '$.createdAt');
                }
            };
        }

        foreach ($schema['properties']['verificationMetadata']['required'] as $key) {
            $cases['required metadata '.$key] = static fn () => $invalid(static function ($data) use ($key): void {
                unset($data->verificationMetadata->{$key});
            }, 'FIELD_REQUIRED', '$.verificationMetadata.'.$key);
            $cases['metadata type '.$key] = static fn () => $invalid(static function ($data) use ($key): void {
                $data->verificationMetadata->{$key} = 123;
            }, 'TYPE_INVALID', '$.verificationMetadata.'.$key);
        }
        $cases['unknown metadata field'] = static fn () => $invalid(static function ($data): void {
            $data->verificationMetadata->extra = false;
        }, 'FIELD_UNKNOWN', '$.verificationMetadata.extra');
        foreach (['profileId', 'provenanceRef', 'baselineCommit', 'profileSha256'] as $key) {
            $code = in_array($key, ['baselineCommit', 'profileSha256'], true) ? 'HASH_INVALID' : 'IDENTIFIER_INVALID';
            $cases['metadata lexical '.$key] = static fn () => $invalid(static function ($data) use ($key): void {
                $data->verificationMetadata->{$key} = 'not valid';
            }, $code, '$.verificationMetadata.'.$key);
        }

        foreach (['files' => 'file', 'historicalArtifacts' => 'history'] as $field => $definition) {
            foreach ($schema['$defs'][$definition]['required'] as $key) {
                $cases['required entry '.$field.'.'.$key] = static fn () => $invalid(static function ($data) use ($field, $key): void {
                    unset($data->{$field}[0]->{$key});
                }, 'FIELD_REQUIRED', '$.'.$field.'[0].'.$key);
                $cases['entry type '.$field.'.'.$key] = static fn () => $invalid(static function ($data) use ($field, $key): void {
                    $data->{$field}[0]->{$key} = null;
                }, 'TYPE_INVALID', '$.'.$field.'[0].'.$key);
            }
            $cases['unknown entry '.$field] = static fn () => $invalid(static function ($data) use ($field): void {
                $data->{$field}[0]->extra = 'x';
            }, 'FIELD_UNKNOWN', '$.'.$field.'[0].extra');
            $cases['empty inventory '.$field] = static fn () => $invalid(static function ($data) use ($field): void {
                $data->{$field} = [];
            }, 'INVENTORY_EMPTY', '$.'.$field);
            $cases['inventory object '.$field] = static fn () => $invalid(static function ($data) use ($field): void {
                $data->{$field} = (object) [];
            }, 'TYPE_INVALID', '$.'.$field);
            $cases['sparse inventory '.$field] = static fn () => $invalid(static function ($data) use ($field): void {
                $data->{$field} = [1 => $data->{$field}[0]];
            }, 'TYPE_INVALID', '$.'.$field);
            $cases['malformed entry '.$field] = static fn () => $invalid(static function ($data) use ($field): void {
                $data->{$field} = [false];
            }, 'TYPE_INVALID', '$.'.$field.'[0]');
            foreach ([-1, 2147483649] as $size) {
                $cases['file size range '.$field.' '.$size] = static fn () => $invalid(static function ($data) use ($field, $size): void {
                    $data->{$field}[0]->sizeBytes = $size;
                }, 'SIZE_INVALID', '$.'.$field.'[0].sizeBytes');
            }
            foreach ([1.0, '1', false] as $index => $size) {
                $cases['file size type '.$field.' '.$index] = static fn () => $invalid(static function ($data) use ($field, $size): void {
                    $data->{$field}[0]->sizeBytes = $size;
                }, 'TYPE_INVALID', '$.'.$field.'[0].sizeBytes');
            }
            foreach (['short', str_repeat('A', 64), str_repeat('g', 64), str_repeat('a', 65), str_repeat('a', 64)."\n"] as $index => $hash) {
                $cases['digest syntax '.$field.' '.$index] = static fn () => $invalid(static function ($data) use ($field, $hash): void {
                    $data->{$field}[0]->sha256 = $hash;
                }, 'HASH_INVALID', '$.'.$field.'[0].sha256');
            }
        }

        foreach ($schema['$defs']['file']['properties']['category']['enum'] as $category) {
            $cases['category '.$category] = static function () use ($category): void {
                $data = self::manifest();
                $data->files[0]->category = $category;
                self::ensure((new ManifestValidator)->validate($data)->valid);
            };
        }
        $cases['invalid category'] = static fn () => $invalid(static function ($data): void {
            $data->files[0]->category = 'executable';
        }, 'CATEGORY_INVALID', '$.files[0].category');
        $cases['invalid history provenance'] = static fn () => $invalid(static function ($data): void {
            $data->historicalArtifacts[0]->provenanceRef = 'has space';
        }, 'IDENTIFIER_INVALID', '$.historicalArtifacts[0].provenanceRef');

        $badPaths = [
            '', '/', '/etc/file', 'C:/file.txt', 'C:file.txt', '\\server\share\file',
            '//server/share/file', '../file', 'a/../file', './file', 'a/./file', 'a//file',
            'a\\file', 'file:stream', 'file.txt::$DATA', 'file.', 'file ', 'folder./file',
            'folder /file', 'file/', 'CON', 'con.txt', 'PRN.log', 'AUX', 'nul.ext',
            'COM1', 'com9.dll', 'LPT1.txt', 'lpt9', 'a/Con.txt', 'CONIN$', 'CONOUT$',
            "file\n", "file\0", "caf\u{00E9}.txt", "cafe\u{0301}.txt", str_repeat('a', 241),
            '\\?\C:\file', '\\.\NUL',
        ];
        foreach ($badPaths as $index => $path) {
            $cases['unsafe path '.$index] = static fn () => $invalid(static function ($data) use ($path): void {
                $data->files[0]->path = $path;
            }, 'PATH_INVALID', '$.files[0].path');
        }
        $safePaths = ['a', '.config', 'a..b.txt', 'COM0.txt', 'COM10.txt', 'console.txt', 'payload/valid-file_1.txt', str_repeat('a', 240)];
        foreach ($safePaths as $index => $path) {
            $cases['lexically valid path '.$index] = static function () use ($path): void {
                $data = self::manifest();
                $data->files[0]->path = $path;
                self::ensure((new ManifestValidator)->validate($data)->valid);
            };
        }

        foreach (['files', 'historicalArtifacts'] as $field) {
            $cases['duplicate path '.$field] = static fn () => $invalid(static function ($data) use ($field): void {
                $data->{$field}[] = clone $data->{$field}[0];
            }, 'DUPLICATE_PATH', '$.'.$field.'[1].path');
            $cases['unordered paths '.$field] = static fn () => $invalid(static function ($data) use ($field): void {
                $entry = clone $data->{$field}[0];
                $entry->path = 'aaa.txt';
                $data->{$field}[] = $entry;
            }, 'INVENTORY_ORDER_INVALID', '$.'.$field.'[1].path');
        }
        $cases['case collision within list'] = static fn () => $invalid(static function ($data): void {
            $data->files[0]->path = 'A.txt';
            $entry = clone $data->files[0];
            $entry->path = 'a.txt';
            $data->files[] = $entry;
        }, 'CASE_COLLISION', '$.files[1].path');
        foreach (['duplicate' => 'payload/readme.txt', 'case' => 'PAYLOAD/README.TXT'] as $kind => $path) {
            $cases['cross-inventory '.$kind] = static fn () => $invalid(static function ($data) use ($path): void {
                $data->historicalArtifacts[0]->path = $path;
            }, $kind === 'duplicate' ? 'DUPLICATE_PATH' : 'CASE_COLLISION', '$.historicalArtifacts[0].path');
        }

        $cases['per-inventory entry limit'] = static fn () => $invalid(static function ($data): void {
            $data->files = array_fill(0, 100001, $data->files[0]);
        }, 'LIMIT_EXCEEDED', '$.files');
        $cases['combined entry limit'] = static fn () => $invalid(static function ($data): void {
            $data->files = array_fill(0, 60000, $data->files[0]);
            $data->historicalArtifacts = array_fill(0, 40001, $data->historicalArtifacts[0]);
        }, 'LIMIT_EXCEEDED', '$');
        foreach ([0 => true, 1 => false] as $extra => $valid) {
            $cases['combined byte limit '.$extra] = static function () use ($extra, $valid): void {
                $data = self::manifest();
                $data->files = [];
                for ($i = 0; $i < 4; $i++) {
                    $data->files[] = (object) ['path' => 'payload/'.$i.'.txt', 'sizeBytes' => 2147483648,
                        'sha256' => str_repeat('a', 64), 'category' => 'documentation'];
                }
                $data->historicalArtifacts[0]->sizeBytes = $extra;
                $result = (new ManifestValidator)->validate($data);
                self::ensure($result->valid === $valid);
                if (! $valid) {
                    self::expectError($result, 'LIMIT_EXCEEDED', '$');
                }
            };
        }

        $cases['deterministic error ordering and no input mutation'] = static function (): void {
            $data = self::manifest();
            $data->zUnknown = 'z';
            $data->aUnknown = 'a';
            $data->files[0]->sha256 = 'invalid';
            $data->createdAt = 'invalid';
            $before = serialize($data);
            $validator = new ManifestValidator;
            $first = $validator->validate($data)->toArray();
            self::ensure(serialize($data) === $before);
            self::ensure($validator->validate($data)->toArray() === $first);
            $reordered = (object) array_reverse(get_object_vars($data), true);
            self::ensure($validator->validate($reordered)->toArray() === $first);
            $sorted = $first['errors'];
            usort($sorted, static fn ($a, $b) => strcmp($a['path'], $b['path']) ?: strcmp($a['code'], $b['code']) ?: strcmp($a['message'], $b['message']));
            self::ensure($sorted === $first['errors']);
        };
        $cases['valid input immutability'] = static function (): void {
            $data = self::manifest();
            $before = serialize($data);
            self::ensure((new ManifestValidator)->validate($data)->valid);
            self::ensure(serialize($data) === $before);
        };
        $cases['DTO breaks references to caller-owned scalars'] = static function (): void {
            $data = self::manifest();
            $path = 'payload/readme.txt';
            $profile = 'fixture-profile';
            $data->files[0]->path = &$path;
            $data->verificationMetadata->profileId = &$profile;
            $result = (new ManifestValidator)->validate($data);
            self::ensure($result->valid);
            $path = 'changed.txt';
            $profile = 'changed-profile';
            self::ensure($result->manifest->files[0]['path'] === 'payload/readme.txt');
            self::ensure($result->manifest->verificationMetadata['profileId'] === 'fixture-profile');
        };
        $cases['timezone-independent calendar validation'] = static function (): void {
            $previous = date_default_timezone_get();
            try {
                $validator = new ManifestValidator;
                date_default_timezone_set('Pacific/Kiritimati');
                $first = $validator->validate(self::manifest())->toArray();
                date_default_timezone_set('America/Los_Angeles');
                self::ensure($validator->validate(self::manifest())->toArray() === $first);
            } finally {
                date_default_timezone_set($previous);
            }
        };
        $cases['no filesystem calls or side effects during validation'] = static function (): void {
            $data = self::manifest();
            $included = get_included_files();
            NoFilesystem::reset();
            self::ensure(stream_wrapper_unregister('file'));
            try {
                self::ensure(stream_wrapper_register('file', NoFilesystem::class));
                self::ensure(! file_exists('not-a-real-fixture'));
                self::ensure(NoFilesystem::$calls === 1, 'Filesystem trap did not intercept its own probe.');
                NoFilesystem::reset();
                self::ensure((new ManifestValidator)->validate($data)->valid);
                $data->files[0]->path = '../not-opened';
                self::expectError((new ManifestValidator)->validate($data), 'PATH_INVALID', '$.files[0].path');
                self::ensure(NoFilesystem::$calls === 0, 'Validator attempted filesystem access.');
                self::ensure(get_included_files() === $included);
            } finally {
                stream_wrapper_restore('file');
            }
        };

        $cases['schema and PHP share lexical constraints'] = static function () use ($schema, $badPaths, $safePaths, $timestamps): void {
            $pathSchema = $schema['$defs']['path'];
            foreach (array_merge($badPaths, $safePaths) as $path) {
                $schemaValid = strlen($path) >= $pathSchema['minLength'] && strlen($path) <= $pathSchema['maxLength']
                    && self::matches($pathSchema['pattern'], $path);
                foreach ($pathSchema['not']['anyOf'] as $rule) {
                    $schemaValid = $schemaValid && ! self::matches($rule['pattern'], $path);
                }
                $data = self::manifest();
                $data->files[0]->path = $path;
                self::ensure((new ManifestValidator)->validate($data)->valid === $schemaValid, 'Path pattern parity failed.');
            }
            foreach ($timestamps as $timestamp => $valid) {
                $syntaxValid = self::matches($schema['properties']['createdAt']['pattern'], $timestamp);
                self::ensure(! $valid || $syntaxValid, 'Schema rejected a valid calendar timestamp.');
                if (! str_contains($timestamp, '02-29') && ! str_contains($timestamp, '02-30') && ! str_contains($timestamp, '04-31')) {
                    self::ensure($syntaxValid === $valid, 'Timestamp syntax parity failed.');
                }
            }
            self::ensure($schema['$defs']['sizeBytes']['maximum'] === 2147483648);
            self::ensure($schema['properties']['files']['maxItems'] === 100000);
            foreach ([$schema, $schema['properties']['verificationMetadata'], $schema['$defs']['file'], $schema['$defs']['history']] as $objectSchema) {
                self::ensure($objectSchema['additionalProperties'] === false);
                self::ensure(array_keys($objectSchema['properties']) === $objectSchema['required']);
            }
        };

        $cases['R1-A maximum permitted malformed inventory'] = static function (): void {
            $data = self::manifest();
            $data->files = array_fill(0, 99999, (object) []);
            $data->historicalArtifacts = [(object) []];
            $result = (new ManifestValidator)->validate($data);
            self::ensure(! $result->valid && $result->manifest === null);
            self::ensure(count($result->errors) === 100 && $result->diagnosticsTruncated);
            self::ensure($result->toArray()['diagnosticsTruncated'] === true);
            self::expectError($result, 'FIELD_REQUIRED', '$.files[24].category');
            foreach ($result->errors as $error) {
                self::ensure(! str_starts_with($error['path'], '$.historicalArtifacts'));
            }
        };
        $cases['R1-B exactly 100 diagnostics terminates'] = static function (): void {
            $data = self::manifest();
            $data->files = array_fill(0, 25, (object) []);
            $result = (new ManifestValidator)->validate($data);
            self::ensure(! $result->valid && $result->manifest === null);
            self::ensure(count($result->errors) === 100 && $result->diagnosticsTruncated);
        };
        $cases['R1-C below threshold preserves diagnostics'] = static function (): void {
            $data = self::manifest();
            $data->files = array_fill(0, 24, (object) []);
            $result = (new ManifestValidator)->validate($data);
            self::ensure(count($result->errors) === 96 && ! $result->diagnosticsTruncated);
            self::ensure(array_keys($result->toArray()) === ['valid', 'errors']);
            for ($i = 0; $i < 24; $i++) {
                foreach (['path', 'sizeBytes', 'sha256', 'category'] as $field) {
                    self::expectError($result, 'FIELD_REQUIRED', '$.files['.$i.'].'.$field);
                }
            }
        };
        $cases['R1-D global budget spans validation stages'] = static function (): void {
            $data = self::manifest();
            $data->createdAt = 'invalid';
            $data->verificationMetadata->profileSha256 = 'invalid';
            $data->files = array_fill(0, 12, (object) []);
            $data->historicalArtifacts = array_fill(0, 13, (object) []);
            $result = (new ManifestValidator)->validate($data);
            self::ensure(count($result->errors) === 100 && $result->diagnosticsTruncated);
            self::expectError($result, 'TIMESTAMP_INVALID', '$.createdAt');
            self::expectError($result, 'HASH_INVALID', '$.verificationMetadata.profileSha256');
            self::expectError($result, 'FIELD_REQUIRED', '$.files[11].category');
            self::expectError($result, 'FIELD_REQUIRED', '$.historicalArtifacts[12].sizeBytes');
            foreach ($result->errors as $error) {
                self::ensure($error['path'] !== '$.historicalArtifacts[12].sha256');
            }
        };
        $cases['R1-E truncated diagnostics remain deterministic'] = static function (): void {
            $data = self::manifest();
            for ($i = 104; $i >= 0; $i--) {
                $data->{'unknown'.sprintf('%03d', $i)} = false;
            }
            $validator = new ManifestValidator;
            $first = $validator->validate($data)->toArray();
            self::ensure($first['diagnosticsTruncated'] === true && count($first['errors']) === 100);
            self::ensure($validator->validate($data)->toArray() === $first);
            $reordered = (object) array_reverse(get_object_vars($data), true);
            self::ensure($validator->validate($reordered)->toArray() === $first);
            $sorted = $first['errors'];
            usort($sorted, static fn ($a, $b) => strcmp($a['path'], $b['path']) ?: strcmp($a['code'], $b['code']) ?: strcmp($a['message'], $b['message']));
            self::ensure($sorted === $first['errors']);
        };
        $cases['R1-F valid result unchanged after truncated validation'] = static function (): void {
            $validator = new ManifestValidator;
            $invalid = self::manifest();
            $invalid->files = array_fill(0, 25, (object) []);
            self::ensure($validator->validate($invalid)->diagnosticsTruncated);
            $result = $validator->validate(self::manifest());
            self::ensure($result->valid && $result->manifest !== null && ! $result->diagnosticsTruncated);
            self::ensure($result->toArray() === ['valid' => true, 'errors' => []]);
        };
        $cases['R1-G early termination does not mutate input'] = static function (): void {
            $data = self::manifest();
            $data->files = array_fill(0, 99999, (object) []);
            $before = serialize($data);
            self::ensure((new ManifestValidator)->validate($data)->diagnosticsTruncated);
            self::ensure(serialize($data) === $before);
        };
        $cases['R1 immediate termination before later validation stages'] = static function (): void {
            $data = self::manifest();
            for ($i = 0; $i < 100; $i++) {
                $data->{'unknown'.sprintf('%03d', $i)} = false;
            }
            $data->createdAt = 'invalid';
            $result = (new ManifestValidator)->validate($data);
            self::ensure(count($result->errors) === 100 && $result->diagnosticsTruncated);
            foreach ($result->errors as $error) {
                self::ensure($error['code'] === 'FIELD_UNKNOWN');
            }
        };

        return $cases;
    }

    private static function matches(string $pattern, string $value): bool
    {
        return preg_match('~'.$pattern.'~', $value) === 1;
    }

    private static function manifest(): stdClass
    {
        return (object) [
            'formatId' => 'rma-poc-inputs', 'schemaVersion' => '1.0', 'manifestId' => 'fixture-v1',
            'pocVersion' => 'fixture-0.1', 'createdAt' => '2026-11-17T12:00:00Z', 'exampleOnly' => false,
            'files' => [(object) ['path' => 'payload/readme.txt', 'sizeBytes' => 0, 'sha256' => str_repeat('a', 64), 'category' => 'documentation']],
            'historicalArtifacts' => [(object) ['path' => 'history/result.json', 'sizeBytes' => 0, 'sha256' => str_repeat('b', 64), 'provenanceRef' => 'synthetic-history']],
            'verificationMetadata' => (object) ['baselineCommit' => str_repeat('c', 40), 'profileId' => 'fixture-profile', 'profileSha256' => str_repeat('d', 64), 'provenanceRef' => 'synthetic-review'],
        ];
    }

    private static function expectError(ValidationResult $result, string $code, string $path): void
    {
        self::ensure(! $result->valid && $result->manifest === null);
        foreach ($result->errors as $error) {
            if ($error['code'] === $code && $error['path'] === $path && $error['message'] !== '') {
                return;
            }
        }
        throw new RuntimeException('Expected '.$code.' at '.$path.'; received '.json_encode($result->toArray(), JSON_THROW_ON_ERROR));
    }

    private static function ensure(bool $condition, string $message = 'Assertion failed.'): void
    {
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }
}

/** The test replaces PHP's file wrapper briefly; no fixture file is created. */
final class NoFilesystem
{
    public mixed $context;

    public static int $calls = 0;

    public static function reset(): void
    {
        self::$calls = 0;
    }

    public function url_stat(string $path, int $flags): false
    {
        self::$calls++;

        return false;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): false
    {
        self::$calls++;

        return false;
    }

    public function unlink(string $path): false
    {
        self::$calls++;

        return false;
    }

    public function mkdir(string $path, int $mode, int $options): false
    {
        self::$calls++;

        return false;
    }

    public function rmdir(string $path, int $options): false
    {
        self::$calls++;

        return false;
    }

    public function rename(string $from, string $to): false
    {
        self::$calls++;

        return false;
    }

    public function dir_opendir(string $path, int $options): false
    {
        self::$calls++;

        return false;
    }
}
