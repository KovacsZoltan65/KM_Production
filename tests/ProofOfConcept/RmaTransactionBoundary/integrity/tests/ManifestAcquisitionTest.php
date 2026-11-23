<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity\Tests;

use Closure;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use RmaTransactionBoundaryPoc\Integrity\LocalManifestByteSource;
use RmaTransactionBoundaryPoc\Integrity\ManifestAcquisitionFailure;
use RmaTransactionBoundaryPoc\Integrity\ManifestByteSource;
use RmaTransactionBoundaryPoc\Integrity\ManifestSnapshot;
use RmaTransactionBoundaryPoc\Integrity\ManifestSnapshotReader;
use RuntimeException;
use Throwable;

/** Trusted synthetic adapter, never presented as actual-local-file provenance. */
final class AcquisitionSource implements ManifestByteSource
{
    public int $opens = 0;

    public int $closes = 0;

    public int $reads = 0;

    public int $observations = 0;

    public int $position = 0;

    public int $maximumRequested = 0;

    public ?int $expected = null;

    public int $short = 65536;

    public ?string $openFailure = null;

    public bool $readFailure = false;

    public bool $zeroProgress = false;

    public bool $throwOnRead = false;

    public bool $closeFailure = false;

    public bool $drift = false;

    public bool $statFailure = false;

    public bool $oversizedChunk = false;

    public function __construct(public int $length = 3, public string $pattern = 'a') {}

    public function open(): void
    {
        $this->opens++;
        if ($this->openFailure !== null) {
            throw new ManifestAcquisitionFailure($this->openFailure);
        }
    }

    public function expectedLength(): ?int
    {
        return $this->expected;
    }

    public function read(int $maximumBytes): string|false
    {
        $this->reads++;
        $this->maximumRequested = max($this->maximumRequested, $maximumBytes);
        if ($this->throwOnRead) {
            throw new LogicException('Synthetic source programming error.');
        }
        if ($this->readFailure) {
            return false;
        }
        if ($this->zeroProgress) {
            return '';
        }
        $count = $this->oversizedChunk ? $maximumBytes + 1 : min($maximumBytes, $this->short, $this->length - $this->position);
        $this->position += $count;

        return str_repeat($this->pattern, $count);
    }

    public function eof(): bool
    {
        return ! $this->zeroProgress && $this->position === $this->length;
    }

    public function stat(): array|false
    {
        $this->observations++;

        return $this->statFailure ? false : ['size' => $this->length, 'mtime' => 1 + (int) ($this->drift && $this->observations > 1), 'ctime' => 1];
    }

    public function close(): bool
    {
        $this->closes++;

        return ! $this->closeFailure;
    }
}

final class ManifestAcquisitionTest
{
    public static int $assertions = 0;

    public static int $fixturesCleaned = 0;

    public static array $negativeEvidence = [];

    public static array $capabilityNotes = [];

    public static array $trailingEvidence = [];

    public static function cases(): array
    {
        $cases = [];
        foreach ([0, 1, 16777215, 16777216, 16777217, 16777218] as $length) {
            $cases['TD05 size '.$length] = static function () use ($length): void {
                $source = new AcquisitionSource($length);
                $r = (new ManifestSnapshotReader)->read($source, 'CONTENT_ONLY');
                self::invariants($r);
                if ($length > 16777216) {
                    self::failed($r, 'MANIFEST_SIZE_EXCEEDED', 'DENIED');
                } else {
                    self::ensure($r->acquired && $r->byteCount === $length && strlen($r->rawBytes) === $length);
                    self::ensure($length === 0 || strspn($r->rawBytes, 'a') === $length);
                }
                self::ensure($source->position === min($length, 16777217));
                self::ensure($source->maximumRequested <= 65536 && $source->opens === 1 && $source->closes === 1);
            };
        }
        $cases['TD05 single open and close'] = static function (): void {
            $s = new AcquisitionSource(19);
            $s->short = 2;
            $s->expected = 19;
            $r = (new ManifestSnapshotReader)->read($s, 'CONTENT_ONLY');
            self::invariants($r);
            self::ensure($r->acquired && $r->rawBytes === str_repeat('a', 19));
            self::ensure($s->opens === 1 && $s->closes === 1 && $s->reads === 10);
        };
        foreach (['open', 'read', 'zero', 'premature', 'negative-length', 'drift', 'stat', 'oversized-chunk', 'close', 'extra-length'] as $kind) {
            $cases['TD05 failure '.$kind] = static function () use ($kind): void {
                $s = new AcquisitionSource;
                match ($kind) {
                    'open' => $s->openFailure = 'IO_READ_FAILED',
                    'read' => $s->readFailure = true,
                    'zero' => $s->zeroProgress = true,
                    'premature' => $s->expected = 4,
                    'negative-length' => $s->expected = -1,
                    'drift' => $s->drift = true,
                    'stat' => $s->statFailure = true,
                    'oversized-chunk' => $s->oversizedChunk = true,
                    'close' => $s->closeFailure = true,
                    'extra-length' => $s->expected = 2,
                };
                $r = (new ManifestSnapshotReader)->read($s, 'CONTENT_ONLY');
                self::failed($r, $kind === 'negative-length' ? 'CONFIG_INVALID' : 'IO_READ_FAILED', $kind === 'negative-length' ? 'DENIED' : 'INCONCLUSIVE');
                self::ensure($s->opens === 1 && $s->closes === ($kind === 'open' ? 0 : 1));
                if ($kind === 'zero') {
                    self::ensure($s->reads === 1);
                }
            };
        }
        foreach (['WINDOWS_OBSERVED', 'NATIVE', '', 'content_only'] as $assurance) {
            $cases['TD05 unsupported assurance '.$assurance] = static function () use ($assurance): void {
                $s = new AcquisitionSource;
                self::failed((new ManifestSnapshotReader)->read($s, $assurance), 'CAPABILITY_UNAVAILABLE', 'INCONCLUSIVE');
                self::ensure($s->opens === 0 && $s->reads === 0 && $s->closes === 0);
            };
        }
        foreach (['PATH_INVALID', 'REPARSE_FOUND', 'FILE_CHANGED', 'CAPABILITY_UNAVAILABLE'] as $code) {
            $cases['TD05 adapter failure '.$code] = static function () use ($code): void {
                $s = new AcquisitionSource;
                $s->openFailure = $code;
                self::failed((new ManifestSnapshotReader)->read($s, 'CONTENT_ONLY'), $code, $code === 'CAPABILITY_UNAVAILABLE' ? 'INCONCLUSIVE' : 'DENIED');
                self::ensure($s->reads === 0 && $s->closes === 0);
            };
        }
        $cases['TD05 first failure beats cleanup failure'] = static function (): void {
            $s = new AcquisitionSource(16777217);
            $s->closeFailure = true;
            self::failed((new ManifestSnapshotReader)->read($s, 'CONTENT_ONLY'), 'MANIFEST_SIZE_EXCEEDED', 'DENIED');
            self::ensure($s->closes === 1);
        };
        $cases['TD05 programming errors propagate with close'] = static function (): void {
            $s = new AcquisitionSource;
            $s->throwOnRead = true;
            self::reject(static fn () => (new ManifestSnapshotReader)->read($s, 'CONTENT_ONLY'), LogicException::class);
            self::ensure($s->closes === 1 && $s->opens === 1);
        };
        $cases['TD05 invalid typed expected length propagates with close'] = static function (): void {
            $source = new class implements ManifestByteSource
            {
                public bool $closed = false;

                public function open(): void {}

                public function expectedLength(): ?int
                {
                    return 'invalid';
                }

                public function read(int $maximumBytes): string|false
                {
                    throw new LogicException('Read must not execute.');
                }

                public function eof(): bool
                {
                    return false;
                }

                public function stat(): array|false
                {
                    throw new LogicException('Stat must not execute.');
                }

                public function close(): bool
                {
                    return $this->closed = true;
                }
            };
            self::reject(static fn () => (new ManifestSnapshotReader)->read($source, 'CONTENT_ONLY'), \TypeError::class);
            self::ensure($source->closed);
        };
        $cases['TD05 repeated reader has no stale state'] = static function (): void {
            $reader = new ManifestSnapshotReader;
            foreach ([false, true, false, true, false] as $failure) {
                $s = new AcquisitionSource(2);
                $s->readFailure = $failure;
                $r = $reader->read($s, 'CONTENT_ONLY');
                self::invariants($r);
                self::ensure($r->acquired === ! $failure && $s->opens === 1 && $s->closes === 1);
                self::ensure($failure ? $r->rawBytes === null : $r->rawBytes === 'aa');
            }
        };
        $cases['TD05 secure result construction'] = static function (): void {
            $reader = new ManifestSnapshotReader;
            $r = $reader->read(new AcquisitionSource, 'CONTENT_ONLY');
            $reflection = new ReflectionClass(ManifestSnapshot::class);
            self::ensure($reflection->isFinal() && $reflection->isReadOnly() && $reflection->getConstructor()->isPrivate());
            self::reject(static fn () => new ManifestSnapshot, \Error::class);
            self::reject(static function () use ($r): void {
                $r->rawBytes = 'forged';
            }, \Error::class);
            self::reject(static fn () => unserialize(serialize($r)), InvalidArgumentException::class);
            $payload = serialize((object) ['acquired' => true, 'rawBytes' => 'forged']);
            $forged = 'O:'.strlen(ManifestSnapshot::class).':"'.ManifestSnapshot::class.'"'.substr($payload, strlen('O:8:"stdClass"'));
            self::reject(static fn () => unserialize($forged), InvalidArgumentException::class);
            $facts = $reader->evaluate(new AcquisitionSource, 'CONTENT_ONLY');
            $facts['acquired'] = true;
            $s = new AcquisitionSource;
            $s->readFailure = true;
            self::failed(ManifestSnapshot::fromReading($reader, $s, 'CONTENT_ONLY'), 'IO_READ_FAILED', 'INCONCLUSIVE');
            self::ensure(! property_exists($r, 'manifestVerdict') && ! property_exists($r, 'authenticity'));
        };
        $cases['TD05 owned local files exact binary and independent calls'] = static function (): void {
            self::fixture(static function (string $payload, string $control, array &$files): void {
                foreach (['binary.bin' => "\0\xff\r\n\n{}\0", 'empty.bin' => ''] as $name => $bytes) {
                    $path = $payload.'/'.$name;
                    self::write($path, $bytes, $files);
                    $reader = new ManifestSnapshotReader;
                    for ($i = 0; $i < 2; $i++) {
                        $r = $reader->read(new LocalManifestByteSource($payload, $path), 'CONTENT_ONLY');
                        self::invariants($r);
                        self::ensure($r->acquired && $r->rawBytes === $bytes && $r->byteCount === strlen($bytes));
                    }
                }
                self::ensure(scandir($control) === ['.', '..']);
            });
        };
        $cases['TD05 actual local cap boundaries'] = static function (): void {
            self::fixture(static function (string $payload, string $control, array &$files): void {
                foreach ([16777215, 16777216, 16777217] as $length) {
                    $path = $payload.'/size-'.$length.'.bin';
                    self::write($path, '', $files);
                    $handle = fopen($path, 'wb');
                    try {
                        for ($remaining = $length; $remaining > 0; $remaining -= $count) {
                            $count = min(65536, $remaining);
                            self::ensure(fwrite($handle, str_repeat('b', $count)) === $count);
                        }
                    } finally {
                        fclose($handle);
                    }
                    $r = (new ManifestSnapshotReader)->read(new LocalManifestByteSource($payload, $path), 'CONTENT_ONLY');
                    if ($length > 16777216) {
                        self::failed($r, 'MANIFEST_SIZE_EXCEEDED', 'DENIED');
                    } else {
                        self::ensure($r->acquired && $r->byteCount === $length && strspn($r->rawBytes, 'b') === $length);
                    }
                }
            });
        };
        $cases['TD05 local source policy and root boundary'] = static function (): void {
            self::fixture(static function (string $payload, string $control, array &$files): void {
                $path = $payload.'/data.bin';
                $outside = $control.'/data.bin';
                self::write($path, 'owned', $files);
                self::write($outside, 'control', $files);
                foreach (['relative.bin', '', 'file://'.$path, 'php://memory', 'https://invalid/a', '\\\\server\\share\\a', '\\\\?\\C:\\a', '\\\\.\\NUL', $path.':ads', $payload.'/../control/data.bin', $payload.'/NUL', $payload.'2/data.bin', $outside] as $invalid) {
                    self::failed((new ManifestSnapshotReader)->read(new LocalManifestByteSource($payload, $invalid), 'CONTENT_ONLY'), 'CONFIG_INVALID', 'DENIED');
                }
                self::failed((new ManifestSnapshotReader)->read(new LocalManifestByteSource($payload, $payload.'/missing'), 'CONTENT_ONLY'), 'IO_READ_FAILED', 'INCONCLUSIVE');
                self::failed((new ManifestSnapshotReader)->read(new LocalManifestByteSource($payload, $payload), 'CONTENT_ONLY'), 'CONFIG_INVALID', 'DENIED');
                self::failed((new ManifestSnapshotReader)->read(new LocalManifestByteSource($payload, $control), 'CONTENT_ONLY'), 'CONFIG_INVALID', 'DENIED');
                self::ensure(file_get_contents($outside) === 'control');
                $source = new LocalManifestByteSource($payload, $path);
                self::ensure((new ManifestSnapshotReader)->read($source, 'CONTENT_ONLY')->acquired);
                self::failed((new ManifestSnapshotReader)->read($source, 'CONTENT_ONLY'), 'CONFIG_INVALID', 'DENIED');
            });
        };
        foreach (PHP_OS_FAMILY === 'Windows' ? ['source-slash', 'source-backslash', 'root-slash', 'root-backslash'] : ['source-slash', 'source-backslash', 'root-slash'] as $variant) {
            $cases['TD05-REV-01 '.$variant] = static function () use ($variant): void {
                self::fixture(static function (string $payload, string $control, array &$files) use ($variant): void {
                    $path = $payload.'/data.bin';
                    self::write($path, 'original-bytes', $files);
                    self::write($control.'/sentinel.bin', 'control', $files);
                    [$exit, $report] = self::trailingProbe($payload, $path, $variant);
                    self::ensure($exit === 0 && $report['passed'] && ! $report['guardRemoved']);
                    if (str_starts_with($variant, 'source-')) {
                        self::ensure(! $report['acquired'] && $report['code'] === 'CONFIG_INVALID' && $report['verdict'] === 'DENIED');
                        self::ensure($report['rawNull'] && $report['byteCount'] === null);
                        self::ensure($report['sourceOpens'] === 0 && $report['sourceReads'] === 0);
                    } else {
                        self::ensure($report['acquired'] && $report['assurance'] === 'CONTENT_ONLY');
                        self::ensure($report['bytesEqual'] && $report['byteCount'] === 14);
                        self::ensure($report['sourceOpens'] === 1 && $report['sourceReads'] > 0);
                    }
                    self::ensure(file_get_contents($path) === 'original-bytes' && file_get_contents($control.'/sentinel.bin') === 'control');
                });
            };
        }
        $cases['TD05-REV-01 guard-removal negative control'] = static function (): void {
            self::fixture(static function (string $payload, string $control, array &$files): void {
                $path = $payload.'/data.bin';
                self::write($path, 'original-bytes', $files);
                foreach (['source-slash', 'source-backslash'] as $variant) {
                    [$exit, $report] = self::trailingProbe($payload, $path, $variant, true);
                    self::ensure($exit === 1 && ! $report['passed'] && $report['guardRemoved']);
                    // The original defect must recur, not an unrelated harness failure.
                    self::ensure($report['acquired'] && $report['code'] === null && $report['bytesEqual']);
                    self::ensure($report['byteCount'] === 14 && $report['sourceOpens'] === 1 && $report['sourceReads'] > 0);
                }
                self::ensure(file_get_contents($path) === 'original-bytes');
            });
        };
        $cases['TD05 actual forbidden symlink capability'] = static function (): void {
            self::fixture(static function (string $payload, string $control, array &$files): void {
                $target = $payload.'/target.bin';
                $link = $payload.'/link.bin';
                self::write($target, 'owned-target', $files);
                $created = false;
                if (function_exists('symlink')) {
                    set_error_handler(static fn (): bool => true);
                    try {
                        $created = symlink($target, $link);
                    } finally {
                        restore_error_handler();
                    }
                }
                if (! $created) {
                    self::$capabilityNotes['actualSymlink'] = 'UNAVAILABLE: ordinary-user creation unsupported; injected REPARSE_FOUND tested separately';

                    return;
                }
                try {
                    self::failed((new ManifestSnapshotReader)->read(new LocalManifestByteSource($payload, $link), 'CONTENT_ONLY'), 'REPARSE_FOUND', 'DENIED');
                    self::$capabilityNotes['actualSymlink'] = 'PASS: positively identified link rejected';
                } finally {
                    // This one registered link is unlinked itself, never its target.
                    if (! is_link($link) || readlink($link) !== $target
                        || str_replace('\\', '/', (string) realpath(dirname($link))) !== $payload) {
                        throw new RuntimeException('Ambiguous owned link; retained: '.$payload);
                    }
                    if (! unlink($link)) {
                        throw new RuntimeException('Owned link retained: '.$payload);
                    }
                }
                self::ensure(file_get_contents($target) === 'owned-target');
            });
        };
        $cases['TD05 no downstream dependencies acquired'] = static function (): void {
            $before = get_included_files();
            $s = new AcquisitionSource(16777217);
            self::failed((new ManifestSnapshotReader)->read($s, 'CONTENT_ONLY'), 'MANIFEST_SIZE_EXCEEDED', 'DENIED');
            self::ensure(get_included_files() === $before);
            self::ensure($s->position === 16777217 && $s->observations === 1);
        };
        $cases['TD05 actual disabled acquisition capabilities'] = static function (): void {
            foreach (['fopen', 'fread', 'fstat'] as $function) {
                $script = <<<'PHP'
                $base = $argv[1];
                require $base.'/src/ManifestByteSource.php';
                require $base.'/src/ManifestSnapshot.php';
                require $base.'/src/ManifestSnapshotReader.php';
                $source = new \RmaTransactionBoundaryPoc\Integrity\LocalManifestByteSource('invalid', 'invalid');
                $r = (new \RmaTransactionBoundaryPoc\Integrity\ManifestSnapshotReader)->read($source, 'CONTENT_ONLY');
                if ($r->errorCode !== 'CAPABILITY_UNAVAILABLE' || $r->verdict !== 'INCONCLUSIVE' || $r->rawBytes !== null) { exit(1); }
                echo 'CAPABILITY_UNAVAILABLE';
                PHP;
                $command = [PHP_BINARY, '-n', '-d', 'disable_functions='.$function, '-r', $script, dirname(__DIR__)];
                $p = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                self::ensure(is_resource($p));
                fclose($pipes[0]);
                $out = stream_get_contents($pipes[1]);
                $err = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                self::ensure(proc_close($p) === 0 && $out === 'CAPABILITY_UNAVAILABLE' && $err === '');
            }
        };
        $cases['TD05 isolated negative controls'] = static function (): void {
            foreach (['size', 'reopen', 'close'] as $mutation) {
                $script = <<<'PHP'
                $base = $argv[1];
                require $base.'/src/ManifestByteSource.php';
                require $base.'/src/ManifestSnapshot.php';
                $code = str_replace("\r\n", "\n", file_get_contents($base.'/src/ManifestSnapshotReader.php'));
                [$find, $replace, $case] = match ($argv[2]) {
                    'size' => ["if (\$total > self::MAX_RAW_BYTES)", 'if (false)', 'TD05 size 16777217'],
                    'reopen' => ['\$expected = \$source->expectedLength();', '\$source->open(); \$expected = \$source->expectedLength();', 'TD05 single open and close'],
                    'close' => ['\$closed = \$source->close();', '\$closed = true;', 'TD05 single open and close'],
                };
                $find = str_replace('\\$', '$', $find);
                $replace = str_replace('\\$', '$', $replace);
                $code = str_replace($find, $replace, $code, $count);
                if ($count !== 1) { throw new RuntimeException('Mutation site mismatch.'); }
                eval(substr($code, 5));
                require $base.'/tests/ManifestAcquisitionTest.php';
                try {
                    \RmaTransactionBoundaryPoc\Integrity\Tests\ManifestAcquisitionTest::cases()[$case]();
                } catch (RuntimeException $error) {
                    if (! str_starts_with($error->getMessage(), 'TD05 assertion ')) { throw $error; }
                    echo 'DETECTED '.$argv[2];
                    exit(1);
                }
                echo 'MUTANT SURVIVED';
                PHP;
                $command = [PHP_BINARY, '-n', '-r', $script, dirname(__DIR__), $mutation];
                $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                self::ensure(is_resource($process));
                fclose($pipes[0]);
                $out = stream_get_contents($pipes[1]);
                $err = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                self::ensure($exit === 1 && $err === '' && $out === 'DETECTED '.$mutation);
                self::$negativeEvidence[$mutation] = $exit;
            }
        };

        return $cases;
    }

    /** Observe actual adapter fopen/fread calls in an isolated namespace, delegating to PHP. */
    private static function trailingProbe(string $root, string $path, string $variant, bool $mutate = false): array
    {
        $script = <<<'PHP'
        namespace RmaTransactionBoundaryPoc\Integrity {
            function fopen(string $path, string $mode): mixed {
                $GLOBALS['td05SourceOpens']++;
                return \fopen($path, $mode);
            }
            function fread(mixed $stream, int $length): string|false {
                $GLOBALS['td05SourceReads']++;
                return \fread($stream, $length);
            }
        }
        namespace {
            [$base, $root, $path, $variant, $mutation] = array_slice($argv, 1);
            $GLOBALS['td05SourceOpens'] = $GLOBALS['td05SourceReads'] = 0;
            if ($mutation === '1') {
                $code = file_get_contents($base.'/src/ManifestByteSource.php');
                $guard = <<<'GUARD'
        str_ends_with($this->sourcePath, '/') || str_ends_with($this->sourcePath, '\\')
        GUARD;
                $code = str_replace($guard, 'false', $code, $count);
                if ($count !== 1) { throw new \RuntimeException('Trailing guard mutation site mismatch.'); }
                eval(substr($code, 5));
            } else {
                require $base.'/src/ManifestByteSource.php';
            }
            require $base.'/src/ManifestSnapshot.php';
            require $base.'/src/ManifestSnapshotReader.php';
            if ($variant === 'source-slash') { $path .= '/'; }
            if ($variant === 'source-backslash') { $path = str_replace('/', DIRECTORY_SEPARATOR, $path).'\\'; }
            if ($variant === 'root-slash') { $root .= '/'; }
            if ($variant === 'root-backslash') { $root = str_replace('/', DIRECTORY_SEPARATOR, $root).'\\'; }
            $r = (new \RmaTransactionBoundaryPoc\Integrity\ManifestSnapshotReader)->read(new \RmaTransactionBoundaryPoc\Integrity\LocalManifestByteSource($root, $path), 'CONTENT_ONLY');
            $opens = $GLOBALS['td05SourceOpens'];
            $reads = $GLOBALS['td05SourceReads'];
            $sourceVariant = str_starts_with($variant, 'source-');
            $passed = $sourceVariant
                ? ! $r->acquired && $r->errorCode === 'CONFIG_INVALID' && $r->verdict === 'DENIED' && $r->rawBytes === null && $r->byteCount === null && $opens === 0 && $reads === 0
                : $r->acquired && $r->assurance === 'CONTENT_ONLY' && $r->rawBytes === 'original-bytes' && $r->byteCount === 14 && $opens === 1 && $reads > 0;
            echo json_encode(['variant' => $variant, 'guardRemoved' => $mutation === '1', 'passed' => $passed, 'acquired' => $r->acquired, 'assurance' => $r->assurance, 'code' => $r->errorCode, 'verdict' => $r->verdict, 'rawNull' => $r->rawBytes === null, 'byteCount' => $r->byteCount, 'bytesEqual' => $r->rawBytes === 'original-bytes', 'sourceOpens' => $opens, 'sourceReads' => $reads], JSON_THROW_ON_ERROR);
            exit($passed ? 0 : 1);
        }
        PHP;
        $command = [PHP_BINARY, '-n', '-r', $script, dirname(__DIR__), $root, $path, $variant, $mutate ? '1' : '0'];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::ensure(is_resource($process));
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        self::ensure($err === '');
        $report = json_decode($out, true, 16, JSON_THROW_ON_ERROR);
        self::$trailingEvidence[$variant.($mutate ? '-mutant' : '')] = ['exit' => $exit] + $report;

        return [$exit, $report];
    }

    private static function invariants(ManifestSnapshot $r): void
    {
        self::ensure($r->assurance === 'CONTENT_ONLY' && $r->executionAuthorization === 'NOT_EVALUATED' && $r->canLaunchPoC === false);
        self::ensure($r->acquired ? $r->errorCode === null && $r->verdict === null && $r->errors === [] : $r->rawBytes === null && $r->byteCount === null);
    }

    private static function failed(ManifestSnapshot $r, string $code, string $verdict): void
    {
        self::invariants($r);
        self::ensure(! $r->acquired && $r->errorCode === $code && $r->verdict === $verdict);
        self::ensure(count($r->errors) === 1 && $r->errors[0]['stage'] === 'G03' && $r->errors[0]['path'] === '$' && $r->errors[0]['code'] === $code);
        self::ensure(! str_contains($r->errors[0]['message'], ':\\') && ! str_contains($r->errors[0]['message'], 'control/data'));
    }

    private static function ensure(bool $condition): void
    {
        self::$assertions++;
        if (! $condition) {
            throw new RuntimeException('TD05 assertion '.self::$assertions.' failed.');
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

    private static function write(string $path, string $bytes, array &$files): void
    {
        $handle = fopen($path, 'xb');
        if ($handle === false) {
            throw new RuntimeException('Cannot exclusively create owned fixture.');
        }
        $files[] = $path;
        try {
            self::ensure($bytes === '' || fwrite($handle, $bytes) === strlen($bytes));
        } finally {
            fclose($handle);
        }
    }

    /** Exact registered cleanup only; ambiguity retains the whole fixture and reports its path. */
    private static function fixture(Closure $test): void
    {
        $temp = realpath(sys_get_temp_dir());
        if ($temp === false) {
            throw new RuntimeException('No explicit local temporary parent available.');
        }
        $parent = str_replace('\\', '/', $temp).'/km-td05-'.getmypid().'-'.bin2hex(random_bytes(12));
        $dirs = [$parent, $parent.'/payload', $parent.'/control'];
        $files = [];
        foreach ($dirs as $dir) {
            if (! mkdir($dir)) {
                throw new RuntimeException('Fixture creation failed; retained: '.$parent);
            }
        }
        try {
            $test($dirs[1], $dirs[2], $files);
        } finally {
            foreach ($dirs as $dir) {
                if (is_link($dir) || str_replace('\\', '/', (string) realpath($dir)) !== $dir) {
                    throw new RuntimeException('Ambiguous fixture identity; retained: '.$parent);
                }
                $expected = $dir === $parent ? ['control', 'payload'] : [];
                foreach ($files as $file) {
                    if (dirname($file) === $dir) {
                        $expected[] = basename($file);
                    }
                }
                sort($expected, SORT_STRING);
                $actual = array_values(array_diff(scandir($dir), ['.', '..']));
                sort($actual, SORT_STRING);
                if ($actual !== $expected) {
                    throw new RuntimeException('Unexpected fixture contents; retained: '.$parent);
                }
            }
            foreach ($files as $file) {
                if (! in_array(dirname($file), [$dirs[1], $dirs[2]], true) || is_link($file)
                    || str_replace('\\', '/', (string) realpath($file)) !== $file || ! is_file($file)) {
                    throw new RuntimeException('Ambiguous fixture file; retained: '.$parent);
                }
            }
            foreach (array_reverse($files) as $file) {
                if (! unlink($file)) {
                    throw new RuntimeException('Fixture cleanup failed; retained: '.$parent);
                }
            }
            foreach (array_reverse($dirs) as $dir) {
                if (! rmdir($dir)) {
                    throw new RuntimeException('Fixture directory retained: '.$parent);
                }
            }
            self::$fixturesCleaned++;
        }
    }
}
