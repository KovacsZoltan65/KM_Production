<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity\Tests;

use Closure;
use ReflectionMethod;
use RmaTransactionBoundaryPoc\Integrity\CanonicalJson;
use RmaTransactionBoundaryPoc\Integrity\Manifest;
use RmaTransactionBoundaryPoc\Integrity\ManifestValidator;
use RmaTransactionBoundaryPoc\Integrity\StrictJsonTokenizer;
use RuntimeException;
use stdClass;
use Throwable;
use UnexpectedValueException;

final class CanonicalJsonTest
{
    public static int $assertions = 0;

    // Independent Python 3 json.dumps(sort_keys=True,separators=(',',':')) + LF,
    // hashlib SHA-256 reference, established before running CanonicalJson.
    private const ORACLES = [
        'small' => [646, 'c7c49ce3bc5120712956843f36e037614c06aed1901e35b0d67ead7a2bdd31b7'],
        'multi' => [1050, '351e8caebefdcfe7f19b7af109475dadfcc1b98bb04879bc6f042bc2cc918678'],
        'boundary' => [1514, 'd712942ef824e7db97c77d657e18e90115579a42b4ddc55e4934168afb6edd6d'],
        'leap' => [646, '6a0a0f7d277eeaccd4dade12e92cf6a624e62772153a0ba732755f9f0560f0c6'],
        'ascii-heavy' => [14761136, '9c6c23d1d8572c1dbce649b9267189b28eaf9d7e40b450969419cf03c203c74a'],
        'inventory-heavy' => [13700372, '036b460039867b63da67298b803f193349a168aeff8576b27fc9c478df4c58e4'],
        'wire-max' => [16777216, '81af27220c351c06526a05175e9f3430c7af389364e91f94f9836d8c7de2378e'],
        'noncanonical-large' => [14761136, '9c6c23d1d8572c1dbce649b9267189b28eaf9d7e40b450969419cf03c203c74a'],
    ];

    /** @return array<string, Closure(): void> */
    public static function cases(): array
    {
        $cases = [];
        foreach (['small', 'multi', 'boundary', 'leap'] as $id) {
            $cases['TD03 independent golden '.$id] = static function () use ($id): void {
                $expected = match ($id) {
                    'small' => self::smallLiteral(), 'multi' => self::multiLiteral(),
                    'boundary' => self::boundaryLiteral(), 'leap' => self::leapLiteral(),
                };
                self::ensure(self::fixture($id) === $expected);
                if ($id === 'small') {
                    self::ensure($expected === self::smallLiteral());
                }
                if ($id === 'multi') {
                    self::ensure($expected === self::multiLiteral());
                }
                self::oracle($expected, $id);
                $manifest = self::validated($expected);
                $before = serialize($manifest);
                for ($i = 0; $i < 3; $i++) {
                    $actual = CanonicalJson::serialize($manifest);
                    self::ensure($actual === $expected);
                    self::oracle($actual, $id);
                    self::ensure(CanonicalJson::isCanonical($manifest, $expected));
                }
                self::ensure(serialize($manifest) === $before);
                self::ensure(substr($actual, -2) === "}\n" && ! str_contains($actual, "\r"));
            };
        }
        $cases['TD03 root nested insertion order and list order'] = static function (): void {
            $raw = self::multiLiteral();
            $parsed = (new StrictJsonTokenizer)->parse($raw);
            self::ensure($parsed->valid);
            $data = $parsed->value;
            $data->verificationMetadata = (object) array_reverse(get_object_vars($data->verificationMetadata), true);
            foreach (['files', 'historicalArtifacts'] as $field) {
                foreach ($data->{$field} as $i => $entry) {
                    $data->{$field}[$i] = (object) array_reverse(get_object_vars($entry), true);
                }
            }
            $data = (object) array_reverse(get_object_vars($data), true);
            $before = serialize($data);
            $validated = (new ManifestValidator)->validate($data);
            self::ensure($validated->valid);
            self::ensure(CanonicalJson::serialize($validated->manifest) === $raw);
            self::ensure(serialize($data) === $before);
            self::ensure($validated->manifest->files[1]['path'] === 'p/000001');
        };
        $raw = self::smallLiteral();
        $variants = [
            'missing LF' => rtrim($raw, "\n"), 'double LF' => $raw."\n", 'CRLF' => substr($raw, 0, -1)."\r\n",
            'leading space' => ' '.$raw, 'trailing space' => $raw.' ', 'pretty space' => str_replace('":', '": ', $raw),
            'root order' => str_replace('"createdAt":"2026-11-18T00:00:00Z","exampleOnly":false', '"exampleOnly":false,"createdAt":"2026-11-18T00:00:00Z"', $raw),
            'nested order' => str_replace('"category":"documentation","path":"p/000000"', '"path":"p/000000","category":"documentation"', $raw),
            'escaped slash' => str_replace('p/', 'p\\/', $raw),
            'escaped ASCII' => str_replace('"manifestId":"m"', '"manifestId":"\\u006d"', $raw),
        ];
        foreach ($variants as $name => $noncanonical) {
            $cases['TD03 noncanonical '.$name] = static function () use ($noncanonical): void {
                $manifest = self::validated($noncanonical);
                $before = $noncanonical;
                self::ensure(! CanonicalJson::isCanonical($manifest, $noncanonical));
                self::ensure($noncanonical === $before);
                self::ensure(CanonicalJson::isCanonical($manifest, self::smallLiteral()));
            };
        }
        foreach ([
            'duplicate' => str_replace('"manifestId":"m"', '"manifestId":"m","manifestId":"m"', $raw),
            'unicode' => str_replace('"manifestId":"m"', '"manifestId":"\\u00e9"', $raw),
            'float' => str_replace('"sizeBytes":0', '"sizeBytes":0.0', $raw),
            'BOM' => "\xef\xbb\xbf".$raw,
        ] as $name => $invalid) {
            $cases['TD03 earlier parser gate '.$name] = static function () use ($invalid): void {
                self::ensure(! (new StrictJsonTokenizer)->parse($invalid)->valid);
            };
        }
        foreach ([
            'null' => str_replace('"manifestId":"m"', '"manifestId":null', $raw),
            'size' => str_replace('"sizeBytes":0', '"sizeBytes":9223372036854775807', $raw),
            'control' => str_replace('"manifestId":"m"', '"manifestId":"\\u0001"', $raw),
            'short control' => str_replace('"manifestId":"m"', '"manifestId":"\\n"', $raw),
        ] as $name => $invalid) {
            $cases['TD03 earlier schema gate '.$name] = static function () use ($invalid): void {
                $parsed = (new StrictJsonTokenizer)->parse($invalid);
                self::ensure($parsed->valid);
                self::ensure(! (new ManifestValidator)->validate($parsed->value)->valid);
            };
        }
        $cases['TD03 inventory swap stays schema failure'] = static function (): void {
            $data = (new StrictJsonTokenizer)->parse(self::multiLiteral())->value;
            $data->files = array_reverse($data->files);
            self::ensure(! (new ManifestValidator)->validate($data)->valid);
        };
        foreach ([
            ['"', '\\"'], ['\\', '\\\\'], ['/', '/'], [chr(8), '\\b'], [chr(9), '\\t'],
            [chr(10), '\\n'], [chr(12), '\\f'], [chr(13), '\\r'], [chr(0), '\\u0000'],
            [chr(1), '\\u0001'], [chr(31), '\\u001f'], [chr(127), '\\u007f'], ['Az09 ~', 'Az09 ~'],
        ] as $i => [$input, $expected]) {
            $cases['TD03 isolated escape literal '.$i] = static function () use ($input, $expected): void {
                self::ensure((new ReflectionMethod(CanonicalJson::class, 'quote'))->invoke(null, $input) === '"'.$expected.'"');
            };
        }
        $cases['TD03 private primitive boundaries do not extend manifest schema'] = static function (): void {
            $scalar = new ReflectionMethod(CanonicalJson::class, 'scalar');
            foreach ([[0, '0'], [2147483648, '2147483648'], [PHP_INT_MAX, '9223372036854775807'], [true, 'true'], [false, 'false']] as [$input, $expected]) {
                self::ensure($scalar->invoke(null, $input) === $expected);
            }
            foreach ([null, 1.0, -1, [], new stdClass] as $invalid) {
                self::reject(static fn () => $scalar->invoke(null, $invalid), UnexpectedValueException::class);
            }
            $quote = new ReflectionMethod(CanonicalJson::class, 'quote');
            foreach (["\xc3\xa9", "\xff", str_repeat('a', 241)] as $invalid) {
                self::reject(static fn () => $quote->invoke(null, $invalid), UnexpectedValueException::class);
            }
        };
        $cases['TD03 complete ASCII control escape table'] = static function (): void {
            $expected = ['\\u0000', '\\u0001', '\\u0002', '\\u0003', '\\u0004', '\\u0005', '\\u0006', '\\u0007',
                '\\b', '\\t', '\\n', '\\u000b', '\\f', '\\r', '\\u000e', '\\u000f', '\\u0010', '\\u0011', '\\u0012',
                '\\u0013', '\\u0014', '\\u0015', '\\u0016', '\\u0017', '\\u0018', '\\u0019', '\\u001a', '\\u001b',
                '\\u001c', '\\u001d', '\\u001e', '\\u001f'];
            $quote = new ReflectionMethod(CanonicalJson::class, 'quote');
            foreach ($expected as $byte => $escaped) {
                self::ensure($quote->invoke(null, chr($byte)) === '"'.$escaped.'"');
            }
        };
        $cases['TD03 DTO boundary rejects mutable graphs and unsupported representation'] = static function () use ($raw): void {
            $m = self::validated($raw);
            self::reject(static fn () => CanonicalJson::serialize((new StrictJsonTokenizer)->parse($raw)->value), \TypeError::class);
            foreach ([null, 1.0, -1, 2147483649] as $size) {
                $files = $m->files;
                $files[0]['sizeBytes'] = $size;
                self::reject(static fn () => CanonicalJson::serialize(self::copy($m, $files)), UnexpectedValueException::class);
            }
            self::reject(static fn () => CanonicalJson::serialize(self::copy($m, [])), UnexpectedValueException::class);
            $files = $m->files;
            $files[0]['extra'] = 'unsupported';
            self::reject(static fn () => CanonicalJson::serialize(self::copy($m, $files)), UnexpectedValueException::class);
            self::ensure(CanonicalJson::serialize($m) === $raw);
            // D01=A: construction/type alone is deliberately not a schema-validity claim.
            self::ensure((new \ReflectionClass(CanonicalJson::class))->isFinal());
            self::ensure(count((new \ReflectionClass(CanonicalJson::class))->getProperties()) === 0);
        };
        $cases['TD03 derived output bound is not a 16MiB output cap'] = static function (): void {
            $append = new ReflectionMethod(CanonicalJson::class, 'append');
            $out = str_repeat('x', 16777216);
            $args = [&$out, "\n"];
            $append->invokeArgs(null, $args);
            self::ensure(strlen($out) === 16777217);
            $out = str_repeat('x', 44584955);
            $args = [&$out, 'x'];
            self::reject(static fn () => $append->invokeArgs(null, $args), UnexpectedValueException::class);
            self::ensure(strlen($out) === 44584955);
        };
        $cases['TD03 valid raw cap can require cap plus one canonical bytes'] = static function (): void {
            // Architecture WD04 recipe without its LF fits raw cap exactly; G07 must
            // report inequality, not invent a canonical-output 16MiB restriction.
            $raw = substr(str_replace('"provenanceRef":"'.str_repeat('r', 26).'"', '"provenanceRef":"'.str_repeat('r', 27).'"', self::fixture('wire-max')), 0, -1);
            self::ensure(strlen($raw) === 16777216);
            $manifest = self::validated($raw);
            self::ensure(CanonicalJson::serialize($manifest) === $raw."\n");
            self::ensure(! CanonicalJson::isCanonical($manifest, $raw));
        };
        $cases['TD03 final acceptance checks the original clock after validation'] = static function (): void {
            $record = self::record('small', 1, 2, true);
            $record['memoryLimit'] = '512M';
            $start = hrtime(true);
            self::ensure(self::accepted($record, 0, false, '', $start, 'small'));
            foreach ([30, 31, 12513] as $seconds) {
                self::ensure(! self::accepted($record, 0, false, '', $start - $seconds * 1000000000, 'small'));
            }
            foreach ([[null, 0, false, ''], [$record, -1, false, ''], [$record, 1, false, ''], [$record, 0, true, ''], [$record, 0, false, 'fatal']] as [$r, $exit, $timeout, $stderr]) {
                self::ensure(! self::accepted($r, $exit, $timeout, $stderr, $start, 'small'));
            }
            foreach (['php', 'rawBytes', 'peakUsed', 'result'] as $key) {
                $bad = $record;
                unset($bad[$key]);
                self::ensure(! self::accepted($bad, 0, false, '', $start, 'small'));
            }
            $record['result']['canonicalSha256'] = str_repeat('0', 64);
            self::ensure(! self::accepted($record, 0, false, '', $start, 'small'));
        };

        return $cases;
    }

    private static function smallLiteral(): string
    {
        return '{"createdAt":"2026-11-18T00:00:00Z","exampleOnly":false,"files":[{"category":"documentation","path":"p/000000","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0}],"formatId":"rma-poc-inputs","historicalArtifacts":[{"path":"h/000000","provenanceRef":"r","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0}],"manifestId":"m","pocVersion":"v","schemaVersion":"1.0","verificationMetadata":{"baselineCommit":"bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb","profileId":"q","profileSha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","provenanceRef":"r"}}'."\n";
    }

    private static function multiLiteral(): string
    {
        return '{"createdAt":"2026-11-18T00:00:00Z","exampleOnly":false,"files":[{"category":"documentation","path":"p/000000","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0},{"category":"documentation","path":"p/000001","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0},{"category":"documentation","path":"p/000002","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0}],"formatId":"rma-poc-inputs","historicalArtifacts":[{"path":"h/000000","provenanceRef":"r","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0},{"path":"h/000001","provenanceRef":"r","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0}],"manifestId":"m","pocVersion":"v","schemaVersion":"1.0","verificationMetadata":{"baselineCommit":"bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb","profileId":"q","profileSha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","provenanceRef":"r"}}'."\n";
    }

    private static function boundaryLiteral(): string
    {
        return '{"createdAt":"2024-02-29T00:00:00Z","exampleOnly":false,"files":[{"category":"documentation","path":"p/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa000000","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":2147483648}],"formatId":"rma-poc-inputs","historicalArtifacts":[{"path":"h/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa000000","provenanceRef":"rrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0}],"manifestId":"mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm","pocVersion":"vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv","schemaVersion":"1.0","verificationMetadata":{"baselineCommit":"bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb","profileId":"qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq","profileSha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","provenanceRef":"rrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr"}}'."\n";
    }

    private static function leapLiteral(): string
    {
        return '{"createdAt":"2024-02-29T00:00:00Z","exampleOnly":false,"files":[{"category":"documentation","path":"p/000000","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0}],"formatId":"rma-poc-inputs","historicalArtifacts":[{"path":"h/000000","provenanceRef":"r","sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","sizeBytes":0}],"manifestId":"m","pocVersion":"v","schemaVersion":"1.0","verificationMetadata":{"baselineCommit":"bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb","profileId":"q","profileSha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","provenanceRef":"r"}}'."\n";
    }

    private static function validated(string $raw): Manifest
    {
        $parsed = (new StrictJsonTokenizer)->parse($raw);
        self::ensure($parsed->valid);
        $result = (new ManifestValidator)->validate($parsed->value);
        self::ensure($result->valid && $result->manifest !== null);

        return $result->manifest;
    }

    private static function copy(Manifest $m, array $files): Manifest
    {
        return new Manifest($m->formatId, $m->schemaVersion, $m->manifestId, $m->pocVersion, $m->createdAt, $m->exampleOnly, $files, $m->historicalArtifacts, $m->verificationMetadata);
    }

    private static function oracle(string $bytes, string $id): void
    {
        self::ensure(strlen($bytes) === self::ORACLES[$id][0]);
        self::ensure(\hash('sha256', $bytes) === self::ORACLES[$id][1]);
    }

    private static function ensure(bool $condition): void
    {
        self::$assertions++;
        if (! $condition) {
            throw new RuntimeException('TD03 assertion '.self::$assertions.' failed.');
        }
    }

    private static function reject(Closure $call, string $type): void
    {
        $caught = false;
        try {
            $call();
        } catch (Throwable $error) {
            $caught = $error instanceof $type;
        }
        self::ensure($caught);
    }

    /** Independent fixed test recipes, not the serializer under test. */
    private static function fixture(string $id): string
    {
        $wire = $id === 'wire-max';
        $long = $wire || in_array($id, ['ascii-heavy', 'noncanonical-large', 'boundary'], true);
        [$files, $history] = match ($id) {
            'small', 'boundary', 'leap' => [1, 1], 'multi' => [3, 2],
            'ascii-heavy', 'noncanonical-large' => [40000, 1], 'inventory-heavy' => [99999, 1], 'wire-max' => [1, 38042],
            default => throw new RuntimeException('Unknown TD03 fixture.'),
        };
        $idLength = $wire || ! $long ? 1 : 80;
        $raw = '{"createdAt":"'.(in_array($id, ['boundary', 'leap'], true) ? '2024-02-29' : '2026-11-18').'T00:00:00Z","exampleOnly":false,"files":[';
        for ($i = 0; $i < $files; $i++) {
            $path = 'p/'.($long ? str_repeat('a', 232) : '').sprintf('%06d', $i);
            $raw .= ($i === 0 ? '' : ',').'{"category":"documentation","path":"'.$path.'","sha256":"'.str_repeat('a', 64).'","sizeBytes":'.($id === 'boundary' ? '2147483648' : '0').'}';
        }
        $raw .= '],"formatId":"rma-poc-inputs","historicalArtifacts":[';
        for ($i = 0; $i < $history; $i++) {
            $path = 'h/'.($long ? str_repeat('a', 232) : '').sprintf('%06d', $i);
            $ref = str_repeat('r', $wire ? ($i === 0 ? 26 : 80) : $idLength);
            $raw .= ($i === 0 ? '' : ',').'{"path":"'.$path.'","provenanceRef":"'.$ref.'","sha256":"'.str_repeat('a', 64).'","sizeBytes":0}';
        }

        return $raw.'],"manifestId":"'.str_repeat('m', $idLength).'","pocVersion":"'.str_repeat('v', $idLength).'","schemaVersion":"1.0","verificationMetadata":{"baselineCommit":"'.str_repeat('b', 40).'","profileId":"'.str_repeat('q', $idLength).'","profileSha256":"'.str_repeat('a', 64).'","provenanceRef":"'.str_repeat('r', $idLength).'"}}'."\n";
    }

    public static function memoryIds(): array
    {
        return ['small', 'multi', 'ascii-heavy', 'inventory-heavy', 'wire-max', 'noncanonical-large'];
    }

    public static function memoryCase(string $id): array
    {
        if (! in_array($id, self::memoryIds(), true)) {
            throw new RuntimeException('Unknown TD03 memory case.');
        }
        $expected = self::fixture($id);
        self::oracle($expected, $id);
        $raw = ($id === 'noncanonical-large' ? ' ' : '').$expected;
        self::ensure(strlen($raw) <= 16777216);
        $manifest = self::validated($raw);
        $before = serialize($manifest);
        $canonical = CanonicalJson::serialize($manifest);
        self::ensure($canonical === $expected);
        self::oracle($canonical, $id);
        $equal = CanonicalJson::isCanonical($manifest, $raw);
        self::ensure($equal === ($id !== 'noncanonical-large'));
        self::ensure(serialize($manifest) === $before);

        $result = ['equal' => $equal, 'canonicalBytes' => strlen($canonical), 'canonicalSha256' => \hash('sha256', $canonical)];

        return [
            'scenario' => $id, 'php' => PHP_VERSION, 'integerBytes' => PHP_INT_SIZE, 'memoryLimit' => ini_get('memory_limit'),
            'rawBytes' => strlen($raw), 'result' => $result,
            'peakUsed' => memory_get_peak_usage(false), 'peakAllocated' => memory_get_peak_usage(true),
        ];
    }

    private static function record(string $id, int $used, int $allocated, bool $equal): array
    {
        return [
            'scenario' => $id, 'php' => PHP_VERSION, 'integerBytes' => PHP_INT_SIZE, 'memoryLimit' => ini_get('memory_limit'),
            'rawBytes' => self::ORACLES[$id][0] + ($id === 'noncanonical-large' ? 1 : 0),
            'peakUsed' => $used, 'peakAllocated' => $allocated,
            'result' => ['equal' => $equal, 'canonicalBytes' => self::ORACLES[$id][0], 'canonicalSha256' => self::ORACLES[$id][1]],
        ];
    }

    public static function memorySuite(string $runner): int
    {
        $failed = 0;
        $signatures = [];
        foreach (self::memoryIds() as $id) {
            for ($repeat = 1; $repeat <= 3; $repeat++) {
                $pipes = [];
                $command = [PHP_BINARY, '-n', '-d', 'memory_limit=512M', $runner, '--td03-memory-case='.$id];
                $start = hrtime(true);
                $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]);
                if (! is_resource($process)) {
                    throw new RuntimeException('Cannot start TD03 memory child.');
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
                        if (! proc_terminate($process)) {
                            throw new RuntimeException('Cannot terminate TD03 memory child.');
                        }
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
                $ok = self::accepted($record, $exit, $timeout, $stderr, $start, $id, $elapsed) && $repeatable;
                $timeout = $timeout || $elapsed >= 30;
                $failed += $ok ? 0 : 1;
                echo json_encode(['repeat' => $repeat, 'exit' => $exit, 'timeout' => $timeout, 'pass' => $ok, 'elapsedSeconds' => round($elapsed, 3), 'record' => $record, 'stderr' => $stderr], JSON_THROW_ON_ERROR).PHP_EOL;
            }
        }
        echo 'TD03 memory: 18 runs; '.(18 - $failed).' passed; '.$failed.' failed.'.PHP_EOL;

        return $failed === 0 ? 0 : 1;
    }

    private static function accepted(?array $record, int $exit, bool $timeout, string $stderr, int $start, string $id, ?float &$elapsed = null): bool
    {
        $expected = [
            'equal' => $id !== 'noncanonical-large', 'canonicalBytes' => self::ORACLES[$id][0],
            'canonicalSha256' => self::ORACLES[$id][1],
        ];
        $valid = ! $timeout && $exit === 0 && $stderr === '' && $record !== null
            && ($record['scenario'] ?? null) === $id
            && is_string($record['php'] ?? null) && version_compare($record['php'], '8.4.0', '>=')
            && ($record['integerBytes'] ?? null) === 8 && ($record['memoryLimit'] ?? null) === '512M'
            && ($record['rawBytes'] ?? null) === self::ORACLES[$id][0] + ($id === 'noncanonical-large' ? 1 : 0)
            && is_int($record['peakUsed'] ?? null) && is_int($record['peakAllocated'] ?? null)
            && $record['peakUsed'] > 0 && $record['peakUsed'] <= $record['peakAllocated'] && $record['peakAllocated'] <= 536870912
            && ($record['result'] ?? null) === $expected;
        $elapsed = (hrtime(true) - $start) / 1e9;

        return $valid && $elapsed >= 0 && $elapsed < 30;
    }
}
