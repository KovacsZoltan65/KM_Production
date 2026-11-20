<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity\Tests;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use RmaTransactionBoundaryPoc\Integrity\JsonParseResult;
use RmaTransactionBoundaryPoc\Integrity\ManifestValidator;
use RmaTransactionBoundaryPoc\Integrity\StrictJsonScanState;
use RmaTransactionBoundaryPoc\Integrity\StrictJsonTokenizer;
use RuntimeException;
use stdClass;
use Throwable;

final class StrictJsonTest
{
    public static int $assertions = 0;

    /** @return array<string, Closure(): void> */
    public static function cases(): array
    {
        $cases = [];
        foreach ([
            ['null', null], ['true', true], ['false', false], ['0', 0], ['123', 123],
            ['9223372036854775807', PHP_INT_MAX], ['""', ''], ['"abc"', 'abc'],
            ['"\u0041\/\\\\\""', 'A/\\"'],
            ['"\b\f\n\r\t\u0000\u007f"', chr(8).chr(12)."\n\r\t\0\x7f"],
            ['[]', []], ['{}', new stdClass], ['[null,true,0,"a",{}]', [null, true, 0, 'a', new stdClass]],
            ['{"b":2,"a":1}', (object) ['b' => 2, 'a' => 1]],
            [" \t\r\n[0]\r\n ", [0]], ['{"a'.chr(92).'"b":1}', (object) ['a"b' => 1]],
        ] as $index => [$raw, $expected]) {
            $cases['TD02 positive representation '.$index] = static function () use ($raw, $expected): void {
                foreach ([false, true] as $factory) {
                    $result = self::parse($raw, $factory);
                    self::ensure($result->valid && serialize($result->value) === serialize($expected));
                }
            };
        }
        $negatives = [
            ['', 'JSON_INVALID', 0], [' ', 'JSON_INVALID', 1], ['{', 'JSON_INVALID', 1],
            ['[', 'JSON_INVALID', 1], ['[1,]', 'JSON_INVALID', 3], ['{"a":1,}', 'JSON_INVALID', 7],
            ['{"a" 1}', 'JSON_INVALID', 5], ['{a:1}', 'JSON_INVALID', 1], ['[1 2]', 'JSON_INVALID', 3],
            ['/*x*/null', 'JSON_INVALID', 0], ['null//x', 'JSON_INVALID', 4],
            ['null true', 'JSON_INVALID', 5], ['{}[]', 'JSON_INVALID', 2], ['trueX', 'JSON_INVALID', 4],
            ['tru', 'JSON_INVALID', 3], ['"abc', 'JSON_INVALID', 4], ['"\q"', 'JSON_INVALID', 1],
            ['"\u00GG"', 'JSON_INVALID', 1], ['"\u12', 'JSON_INVALID', 1],
            ["\"a\nb\"", 'JSON_INVALID', 2], ["\"\0\"", 'JSON_INVALID', 1],
            ["\xef\xbb\xbf{}", 'ENCODING_INVALID', 0],
            ['"\uD800"', 'ENCODING_INVALID', 1], ['"\uDC00"', 'ENCODING_INVALID', 1],
            ['"\uD800\u0041"', 'ENCODING_INVALID', 1], ['"\uD800\uZZZZ"', 'JSON_INVALID', 7],
            ['"\uD834\uDD1E"', 'JSON_NON_ASCII', 1], ['"\u00e9"', 'JSON_NON_ASCII', 1],
            ['{"\u0000":0}', 'JSON_KEY_INVALID', 2], ['{"\u007f":0}', 'JSON_KEY_INVALID', 2],
            ['{"a":1,"a":2}', 'JSON_DUPLICATE_KEY', 7],
            ['{"a":1,"\u0061":2}', 'JSON_DUPLICATE_KEY', 7],
            ['{"x":{"a":1,"a":2}}', 'JSON_DUPLICATE_KEY', 12],
            ['01', 'JSON_INVALID', 1], ['00', 'JSON_INVALID', 1], ['-01', 'JSON_INVALID', 2],
            ['-1', 'JSON_NUMBER_INVALID', 0], ['-0', 'JSON_NUMBER_INVALID', 0],
            ['1.0', 'JSON_NUMBER_INVALID', 0], ['1e2', 'JSON_NUMBER_INVALID', 0],
            ['1E+2', 'JSON_NUMBER_INVALID', 0], ['1e309', 'JSON_NUMBER_INVALID', 0],
            ['+1', 'JSON_INVALID', 0], ['NaN', 'JSON_INVALID', 0], ['Infinity', 'JSON_INVALID', 0],
            ['-Infinity', 'JSON_INVALID', 1], ['-', 'JSON_INVALID', 1], ['1.', 'JSON_INVALID', 2],
            ['1e', 'JSON_INVALID', 2], ['1e+', 'JSON_INVALID', 3], ['1x', 'JSON_INVALID', 1],
            ['1 2', 'JSON_INVALID', 2], ['1.e2', 'JSON_INVALID', 2], ['1e-x', 'JSON_INVALID', 3],
            ['9223372036854775808', 'JSON_INTEGER_OVERFLOW', 0],
            ['[9223372036854775808]', 'JSON_INTEGER_OVERFLOW', 1],
            ['999999999999999999999x', 'JSON_INVALID', 21],
            ['999999999999999999999.0', 'JSON_NUMBER_INVALID', 0],
        ];
        foreach ($negatives as $index => [$raw, $code, $offset]) {
            $cases['TD02 exact failure '.$index] = static function () use ($raw, $code, $offset): void {
                foreach ([false, true] as $factory) {
                    self::failure(self::parse($raw, $factory), $code, $offset);
                }
            };
        }
        foreach (["\x80", "\xc0\xaf", "\xc1\xbf", "\xc2", "\xc2A", "\xe0\x80\x80", "\xed\xa0\x80", "\xe2\x82", "\xf0\x80\x80\x80", "\xf4\x90\x80\x80", "\xf5\x80\x80\x80", "\xff"] as $index => $bytes) {
            $cases['TD02 invalid UTF-8 '.$index] = static fn () => self::failure(self::parse('"'.$bytes.'"'), 'ENCODING_INVALID', 1);
        }
        foreach (["\xc2\xa0", "\xe2\x82\xac", "\xf0\x9f\x98\x80", "\xef\xbb\xbf"] as $index => $bytes) {
            $cases['TD02 valid non-ASCII '.$index] = static fn () => self::failure(self::parse('"'.$bytes.'"'), 'JSON_NON_ASCII', 1);
        }
        $cases['TD02 encoding grammar domain precedence'] = static function (): void {
            self::failure(self::parse("\"\xc3\xa9\\q\""), 'JSON_INVALID', 3);
            self::failure(self::parse("\"\xc3\xa9\xff\""), 'ENCODING_INVALID', 3);
            self::failure(self::parse('{"\u0000\q":0}'), 'JSON_INVALID', 8);
        };
        $cases['TD02 keys have independent coercion-safe seen sets'] = static function (): void {
            $raw = '{"a":{},"A":{},"x":{"a":0},"y":{"a":1}}';
            self::ensure(self::parse($raw)->valid);
            $result = self::parse('{"0":0,"00":1,"-0":2,"1":3,"01":4,"1.0":5,"1e0":6}');
            self::ensure($result->valid && count(get_object_vars($result->value)) === 7);
            self::ensure($result->value->{'0'} === 0 && $result->value->{'00'} === 1);
            self::failure(self::parse('{"0":0,"\u0030":1}'), 'JSON_DUPLICATE_KEY', 7);
        };
        foreach ([239, 240, 241] as $size) {
            $cases['TD02 decoded value boundary '.$size] = static function () use ($size): void {
                foreach (['a', '\u0061'] as $unit) {
                    $result = self::parse('"'.str_repeat($unit, $size).'"');
                    if ($size <= 240) {
                        self::ensure($result->valid && $result->value === str_repeat('a', $size));
                    } else {
                        self::failure($result, 'JSON_COMPLEXITY_LIMIT', 1 + 240 * strlen($unit));
                    }
                }
            };
        }
        foreach ([19, 20, 21] as $size) {
            $cases['TD02 decoded key boundary '.$size] = static function () use ($size): void {
                $result = self::parse('{"'.str_repeat('a', $size).'":0}');
                if ($size <= 20) {
                    self::ensure($result->valid);
                } else {
                    self::failure($result, 'JSON_COMPLEXITY_LIMIT', 22);
                }
            };
        }
        foreach ([16, 17] as $depth) {
            $cases['TD02 depth '.$depth] = static function () use ($depth): void {
                $raw = str_repeat('[', $depth).str_repeat(']', $depth);
                if ($depth === 16) {
                    self::ensure(self::parse($raw)->valid);
                } else {
                    self::failure(self::parse($raw), 'JSON_DEPTH_EXCEEDED', 16);
                }
            };
        }
        foreach ([-1, 0, 1] as $delta) {
            $cases['TD02 raw size boundary '.$delta] = static function () use ($delta): void {
                $raw = 'null'.str_repeat(' ', StrictJsonTokenizer::MAX_RAW_BYTES + $delta - 4);
                if ($delta <= 0) {
                    self::ensure(self::parse($raw)->valid);
                } else {
                    self::failure(self::parse($raw), 'MANIFEST_SIZE_EXCEEDED', 16777216);
                }
            };
        }
        $cases['TD02 member budget before tenth key decoding'] = static function (): void {
            $members = [];
            for ($i = 0; $i < 9; $i++) {
                $members[] = '"k'.$i.'":0';
            }
            $prefix = '{'.implode(',', $members);
            self::ensure(self::parse($prefix.'}')->valid);
            self::failure(self::parse($prefix.',"\q":0}'), 'JSON_COMPLEXITY_LIMIT', strlen($prefix) + 1);
        };
        $cases['TD02 array element budget guards forbidden next value'] = static function (): void {
            self::ensure(count(self::parse('['.str_repeat('0,', 99999).'0]')->value) === 100000);
            self::failure(self::parse('['.str_repeat('0,', 100000).'NaN]'), 'JSON_COMPLEXITY_LIMIT', 200001);
        };
        $cases['TD02 global container boundary'] = static function (): void {
            $tail = str_repeat(',[]', 99999).']';
            self::ensure(self::parse('[{"a":{"b":{"c":[]}}}'.$tail)->valid);
            $raw = '[{"a":{"b":{"c":{"d":[]}}}}'.$tail;
            self::failure(self::parse($raw), 'JSON_COMPLEXITY_LIMIT', strrpos($raw, '[]'));
        };
        $cases['TD02 global scalar and member boundaries'] = static function (): void {
            $atLimit = self::countGraph([9, 9, 7], 3);
            self::ensure(self::parse($atLimit)->valid); // C100004, S400010, M400013, E100000.
            $memberOver = self::countGraph([9, 9, 7], 3, true);
            self::failure(self::parse($memberOver), 'JSON_COMPLEXITY_LIMIT', strrpos($memberOver, '"k4"'));
            $scalarOver = self::countGraph([9, 9, 5], 0);
            self::failure(self::parse($scalarOver), 'JSON_COMPLEXITY_LIMIT', strrpos($scalarOver, ':0') + 1);
        };
        $cases['TD02 safe result construction and export boundary'] = static function (): void {
            self::reject(static fn () => new JsonParseResult, \Error::class);
            $facts = (new StrictJsonTokenizer)->evaluate('null');
            self::reject(static fn () => JsonParseResult::fromParsing($facts, 'null'), \TypeError::class);
            self::reject(static fn () => JsonParseResult::fromParsing(new class {}, 'null'), \TypeError::class);
            self::reject(static fn () => JsonParseResult::success((object) ['forged' => true]), \Error::class);
            $parser = new StrictJsonTokenizer;
            $result = self::parse('{"a":0}');
            self::ensure((new ReflectionClass($parser))->isFinal());
            self::ensure((new ReflectionClass($result))->isFinal() && (new ReflectionClass($result))->isReadOnly());
            self::ensure((new ReflectionClass($result))->getConstructor()->isPrivate());
            $methods = array_map(static fn ($method) => $method->getName(), (new ReflectionClass($result))->getMethods(\ReflectionMethod::IS_PUBLIC));
            sort($methods);
            self::ensure($methods === ['__unserialize', 'fromParsing']);
            self::reject(static function () use ($result): void {
                $result->valid = false;
            }, \Error::class);
            $result->value->a = 'mutable graph';
            self::ensure($parser->parse('{"a":0}')->value->a === 0);
            foreach (['null', '{', 'true', '01', '[]'] as $raw) {
                self::ensure(serialize($parser->parse($raw)) === serialize((new StrictJsonTokenizer)->parse($raw)));
            }
        };
        $cases['TD02 native hydration rejected for real and forged states'] = static function (): void {
            foreach (['null', '{'] as $raw) {
                $result = self::parse($raw);
                self::reject(static fn () => unserialize(serialize($result), ['allowed_classes' => [JsonParseResult::class]]), InvalidArgumentException::class);
                self::reject(static fn () => $result->__unserialize(['valid' => true]), InvalidArgumentException::class);
            }
            $payload = serialize((object) ['valid' => true, 'value' => 'forged', 'error' => null]);
            $forged = 'O:'.strlen(JsonParseResult::class).':"'.JsonParseResult::class.'"'.substr($payload, strlen('O:8:"stdClass"'));
            self::reject(static fn () => unserialize($forged, ['allowed_classes' => [JsonParseResult::class]]), InvalidArgumentException::class);
        };
        $cases['TD02 unrelated programming exception is not an input result'] = static function (): void {
            $state = new StrictJsonScanState('{"a":0}');
            (new \ReflectionProperty($state, 'cursor'))->setValue($state, 1);
            (new \ReflectionProperty($state, 'stack'))->setValue($state, [(object) [
                'object' => true, 'state' => 'key', 'members' => 'programming defect',
            ]]);
            self::reject(static fn () => $state->run(), \TypeError::class);
        };
        $cases['TD02 deterministic diagnostics do not expose payload'] = static function (): void {
            $raw = '{"C:\\private-secret":NaN}';
            $first = self::parse($raw);
            for ($i = 0; $i < 3; $i++) {
                self::ensure(self::parse($raw)->error === $first->error);
            }
            self::ensure(! str_contains(json_encode($first->error, JSON_THROW_ON_ERROR), 'private-secret'));
            self::ensure(! str_contains($first->error['message'], 'C:'));
        };
        $cases['TD02 anchor-shaped input is data only and exceeds no 16KiB adapter cap'] = static function (): void {
            $raw = '{"anchorVersion":"1.0","anchorId":"a","manifestId":"m","manifestSha256":"'.str_repeat('a', 64).'","profileId":"p","profileSha256":"'.str_repeat('b', 64).'","approvalRef":"r","verifierReleaseId":"v","status":"ACTIVE"}';
            self::ensure(self::parse($raw.str_repeat(' ', 16384))->valid);
        };
        $cases['TD02 static core dependency and exception boundary'] = static function (): void {
            foreach (['StrictJsonTokenizer', 'JsonParseResult'] as $name) {
                $source = file_get_contents(dirname(__DIR__).'/src/'.$name.'.php');
                foreach (token_get_all($source) as $token) {
                    if (! is_array($token)) {
                        continue;
                    }
                    self::ensure(! in_array($token[0], [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE, T_EVAL], true));
                    if ($token[0] === T_STRING) {
                        self::ensure(! in_array(strtolower($token[1]), ['json_decode', 'hash', 'hash_file', 'file_get_contents', 'fopen', 'file_put_contents', 'proc_open', 'exec', 'getenv', 'getcwd', 'manifestvalidator', 'canonicaljson', 'trustcontext', 'pdo', 'throwable'], true));
                    }
                }
            }
        };
        $cases['TD02 memory observer rejects late completed or missing child evidence'] = static function (): void {
            $record = ['scenario' => 'wire-max', 'php' => PHP_VERSION, 'integerBytes' => 8, 'memoryLimit' => '512M', 'rawBytes' => 16777216, 'peakUsed' => 100, 'peakAllocated' => 200, 'result' => ['valid' => true, 'error' => null]];
            $start = hrtime(true);
            self::ensure(self::memoryAccepted($record, 0, false, '', $start, 'wire-max'));
            // Synthetic past start times model delays without sleeping or resetting the clock.
            foreach ([30, 31, 12513] as $seconds) {
                self::ensure(! self::memoryAccepted($record, 0, false, '', $start - $seconds * 1000000000, 'wire-max'));
            }
            $elapsed = 1.0; // A stale earlier observation must not determine final acceptance.
            self::ensure(! self::memoryAccepted($record, 0, false, '', $start - 31000000000, 'wire-max', $elapsed));
            self::ensure($elapsed >= 31);
            self::ensure(! self::memoryAccepted(null, -1, false, '', $start, 'wire-max'));
            self::ensure(! self::memoryAccepted(null, 0, false, '', $start, 'wire-max'));
            self::ensure(! self::memoryAccepted($record, 1, false, '', $start, 'wire-max'));
            self::ensure(! self::memoryAccepted($record, 0, true, '', $start, 'wire-max'));
            self::ensure(! self::memoryAccepted($record, 0, false, 'fatal', $start, 'wire-max'));
            unset($record['result']);
            self::ensure(! self::memoryAccepted($record, 0, false, '', $start, 'wire-max'));
        };
        $cases['TD02 memory clock precedes launch and cannot be reset'] = static function (): void {
            // Structural check complements behavior tests: it cannot simulate OS scheduling.
            $method = new \ReflectionMethod(self::class, 'memorySuite');
            $lines = file(__FILE__);
            $source = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
            $tokens = '';
            foreach (token_get_all('<?php '.$source) as $token) {
                if (is_array($token) && in_array($token[0], [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $tokens .= is_array($token) ? $token[1] : $token;
            }
            self::ensure(str_contains($tokens, '$start=hrtime(true);$process=proc_open('));
            self::ensure(substr_count($tokens, '$start=') === 1);
            self::ensure(str_contains($tokens, '(hrtime(true)-$start)/1e9>=30'));
            self::ensure(str_contains($tokens, 'self::memoryAccepted($record,$exit,$timeout,$stderr,$start,$id,$elapsed)'));
            $failure = strpos($tokens, 'if(!is_resource($process)){thrownewRuntimeException(');
            self::ensure($failure !== false && $failure < strpos($tokens, 'fclose($pipes[0])'));
        };
        if (function_exists('xdebug_start_function_monitor') && in_array('develop', xdebug_info('mode'), true)) {
            $cases['TD02 monitor observes exactly one evaluation and actual scan'] = static function (): void {
                foreach ([false, true] as $factory) {
                    foreach (['null', '{', str_repeat(' ', 16777217)] as $raw) {
                        xdebug_start_function_monitor([StrictJsonTokenizer::class.'->parse', StrictJsonTokenizer::class.'->evaluate', StrictJsonScanState::class.'->run', JsonParseResult::class.'::fromParsing']);
                        $start = count(xdebug_get_monitored_functions());
                        try {
                            self::parse($raw, $factory);
                            $events = array_slice(xdebug_get_monitored_functions(), $start);
                        } finally {
                            xdebug_stop_function_monitor();
                        }
                        $counts = [];
                        foreach ($events as $event) {
                            $name = str_replace('->', '::', ltrim($event['function'], '\\'));
                            $counts[$name] = ($counts[$name] ?? 0) + 1;
                        }
                        self::ensure(($counts[StrictJsonTokenizer::class.'::parse'] ?? 0) === ($factory ? 0 : 1));
                        self::ensure(($counts[JsonParseResult::class.'::fromParsing'] ?? 0) === 1);
                        self::ensure(($counts[StrictJsonTokenizer::class.'::evaluate'] ?? 0) === 1);
                        self::ensure(($counts[StrictJsonScanState::class.'::run'] ?? 0) === (strlen($raw) > 16777216 ? 0 : 1));
                    }
                }
            };
        }

        return $cases;
    }

    private static function countGraph(array $firstCounts, int $emptyValues, bool $extraLast = false): string
    {
        $raw = '[';
        for ($i = 0; $i < 100000; $i++) {
            $raw .= ($i === 0 ? '' : ',').'{';
            $count = $firstCounts[$i] ?? 4;
            if ($extraLast && $i === 99999) {
                $count++;
            }
            for ($j = 0; $j < $count; $j++) {
                $raw .= ($j === 0 ? '' : ',').'"k'.$j.'":'.($emptyValues-- > 0 ? '[]' : '0');
            }
            $raw .= '}';
        }

        return $raw.']';
    }

    private static function parse(string $raw, bool $factory = false): JsonParseResult
    {
        $parser = new StrictJsonTokenizer;
        $result = $factory ? JsonParseResult::fromParsing($parser, $raw) : $parser->parse($raw);
        self::ensure(array_keys(get_object_vars($result)) === ['valid', 'value', 'error']);
        self::ensure($result->valid ? $result->error === null : ($result->value === null && $result->error !== null));
        if ($result->error !== null) {
            self::ensure(array_keys($result->error) === ['code', 'offset', 'message']);
            self::ensure(is_int($result->error['offset']) && $result->error['offset'] >= 0 && $result->error['offset'] <= strlen($raw));
            self::ensure(strlen($result->error['message']) < 120);
        }

        return $result;
    }

    private static function failure(JsonParseResult $result, string $code, int $offset): void
    {
        self::ensure(! $result->valid && $result->value === null);
        self::ensure($result->error['code'] === $code && $result->error['offset'] === $offset);
    }

    private static function reject(Closure $attempt, string $type): void
    {
        $caught = false;
        try {
            $attempt();
        } catch (Throwable $error) {
            $caught = $error instanceof $type;
        }
        self::ensure($caught);
    }

    private static function ensure(bool $condition): void
    {
        self::$assertions++;
        if (! $condition) {
            throw new RuntimeException('TD02 assertion '.self::$assertions.' failed.');
        }
    }

    public static function memoryIds(): array
    {
        return ['wire-max', 'byte-heavy', 'object-heavy', 'array-heavy', 'invalid-stress', 'defensive-limit'];
    }

    /** In-memory synthetic test recipes, not a canonicalizer or an actual manifest. */
    public static function memoryCase(string $id): array
    {
        if (! in_array($id, self::memoryIds(), true)) {
            throw new InvalidArgumentException('Unknown TD02 memory scenario.');
        }
        if ($id === 'invalid-stress') {
            $raw = str_repeat('9', 16777216);
            $expected = ['JSON_INTEGER_OVERFLOW', 0];
        } elseif ($id === 'defensive-limit') {
            $raw = '"'.str_repeat('a', 16777214).'"';
            $expected = ['JSON_COMPLEXITY_LIMIT', 241];
        } else {
            $raw = self::wireFixture($id);
            $expected = null;
        }
        $bytes = strlen($raw);
        $result = (new StrictJsonTokenizer)->parse($raw);
        if ($expected === null) {
            self::ensure($result->valid);
            // Both graphs remain live during A1.1 validation; this is stronger than parse alone.
            self::ensure((new ManifestValidator)->validate($result->value)->valid);
            if ($id === 'wire-max') {
                self::ensure($bytes === 16777216);
            }
        } else {
            self::failure($result, $expected[0], $expected[1]);
        }

        return [
            'scenario' => $id, 'php' => PHP_VERSION, 'integerBytes' => PHP_INT_SIZE,
            'memoryLimit' => ini_get('memory_limit'), 'rawBytes' => $bytes,
            'peakUsed' => memory_get_peak_usage(false), 'peakAllocated' => memory_get_peak_usage(true),
            'result' => ['valid' => $result->valid, 'error' => $result->error],
        ];
    }

    private static function wireFixture(string $id): string
    {
        $wireMax = $id === 'wire-max';
        $long = $wireMax || $id === 'byte-heavy';
        $files = match ($id) {
            'wire-max' => 1, 'byte-heavy' => 40000, 'array-heavy' => 99999, default => 1
        };
        $history = match ($id) {
            'wire-max' => 38042, 'byte-heavy', 'array-heavy' => 1, default => 99999
        };
        $identifier = str_repeat('m', $wireMax || ! $long ? 1 : 80);
        $profile = str_repeat('q', $wireMax || ! $long ? 1 : 80);
        $provenance = str_repeat('r', $wireMax || ! $long ? 1 : 80);
        $hash = str_repeat('a', 64);
        $raw = '{"createdAt":"2026-11-18T00:00:00Z","exampleOnly":false,"files":[';
        for ($i = 0; $i < $files; $i++) {
            $path = 'p/'.($long ? str_repeat('a', 232) : '').sprintf('%06d', $i);
            $raw .= ($i === 0 ? '' : ',').'{"category":"documentation","path":"'.$path.'","sha256":"'.$hash.'","sizeBytes":0}';
        }
        $raw .= '],"formatId":"rma-poc-inputs","historicalArtifacts":[';
        for ($i = 0; $i < $history; $i++) {
            $path = 'h/'.($long ? str_repeat('a', 232) : '').sprintf('%06d', $i);
            $ref = $wireMax ? str_repeat('r', $i === 0 ? 26 : 80) : $provenance;
            $raw .= ($i === 0 ? '' : ',').'{"path":"'.$path.'","provenanceRef":"'.$ref.'","sha256":"'.$hash.'","sizeBytes":0}';
        }
        $raw .= '],"manifestId":"'.$identifier.'","pocVersion":"'.str_replace('m', 'v', $identifier).'","schemaVersion":"1.0","verificationMetadata":{"baselineCommit":"'.str_repeat('b', 40).'","profileId":"'.$profile.'","profileSha256":"'.$hash.'","provenanceRef":"'.$provenance.'"}}'."\n";

        return $raw;
    }

    /** Fixed child argv, pipe-only evidence, finite budget; no shell or repository artifacts. */
    public static function memorySuite(string $runner): int
    {
        $failed = 0;
        $signatures = [];
        foreach (self::memoryIds() as $id) {
            for ($repeat = 1; $repeat <= 3; $repeat++) {
                $command = [PHP_BINARY, '-n', '-d', 'memory_limit=512M', $runner, '--td02-memory-case='.$id];
                $pipes = [];
                $start = hrtime(true);
                $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]);
                if (! is_resource($process)) {
                    throw new RuntimeException('Cannot start TD02 memory child.');
                }
                fclose($pipes[0]);
                stream_set_blocking($pipes[1], false);
                stream_set_blocking($pipes[2], false);
                $stdout = '';
                $stderr = '';
                $timeout = false;
                do {
                    $stdout .= stream_get_contents($pipes[1]);
                    $stderr .= stream_get_contents($pipes[2]);
                    $status = proc_get_status($process);
                    if (! $status['running']) {
                        break;
                    }
                    if ((hrtime(true) - $start) / 1e9 >= 30) {
                        $timeout = true;
                        proc_terminate($process);
                        break;
                    }
                    usleep(10000);
                } while (true);
                stream_set_blocking($pipes[1], true);
                stream_set_blocking($pipes[2], true);
                $stdout .= stream_get_contents($pipes[1]);
                $stderr .= stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $closed = proc_close($process);
                $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;
                $record = json_decode(trim($stdout), true);
                $signature = is_array($record) ? [$record['rawBytes'] ?? null, $record['result'] ?? null] : null;
                $repeatable = ! isset($signatures[$id]) || $signatures[$id] === $signature;
                $signatures[$id] = $signature;
                // Re-read the original clock inside final acceptance, after record validation.
                $ok = self::memoryAccepted($record, $exit, $timeout, $stderr, $start, $id, $elapsed) && $repeatable;
                $timeout = $timeout || $elapsed >= 30;
                $failed += $ok ? 0 : 1;
                echo json_encode(['repeat' => $repeat, 'exit' => $exit, 'timeout' => $timeout, 'pass' => $ok, 'elapsedSeconds' => round($elapsed, 3), 'record' => $record, 'stderr' => $stderr], JSON_THROW_ON_ERROR).PHP_EOL;
            }
        }
        echo 'TD02 memory: 18 runs; '.(18 - $failed).' passed; '.$failed.' failed.'.PHP_EOL;

        return $failed === 0 ? 0 : 1;
    }

    private static function memoryAccepted(?array $record, int $exit, bool $timeout, string $stderr, int $start, string $id, ?float &$elapsed = null): bool
    {
        $metadata = ! $timeout && $exit === 0 && $stderr === '' && $record !== null
            && ($record['scenario'] ?? null) === $id && ($record['integerBytes'] ?? null) === 8
            && ($record['memoryLimit'] ?? null) === '512M'
            && is_string($record['php'] ?? null) && version_compare($record['php'], '8.4.0', '>=')
            && is_int($record['rawBytes'] ?? null) && $record['rawBytes'] >= 0 && $record['rawBytes'] <= 16777216
            && isset($record['peakUsed'], $record['peakAllocated'])
            && is_int($record['peakUsed']) && is_int($record['peakAllocated'])
            && $record['peakUsed'] >= 0 && $record['peakUsed'] <= $record['peakAllocated']
            && $record['peakAllocated'] <= 536870912
            && is_array($record['result'] ?? null);
        if (! $metadata) {
            $elapsed = (hrtime(true) - $start) / 1e9;

            return false;
        }
        $expected = match ($id) {
            'invalid-stress' => ['JSON_INTEGER_OVERFLOW', 0],
            'defensive-limit' => ['JSON_COMPLEXITY_LIMIT', 241],
            default => null,
        };

        $validResult = $expected === null
            ? $record['result'] === ['valid' => true, 'error' => null]
            : ($record['result']['valid'] === false
                && ($record['result']['error']['code'] ?? null) === $expected[0]
                && ($record['result']['error']['offset'] ?? null) === $expected[1]
                && is_string($record['result']['error']['message'] ?? null));
        $elapsed = (hrtime(true) - $start) / 1e9;

        return $validResult && $elapsed >= 0 && $elapsed < 30;
    }
}
