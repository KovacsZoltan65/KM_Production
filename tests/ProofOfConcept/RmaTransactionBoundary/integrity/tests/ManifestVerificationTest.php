<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity {
    // Test-only validator fault observer. Ordinary calls always delegate to PHP.
    // This permits lifecycle faults with ONE fixed provider/pin and unchanged raw
    // bytes; no mutable provider or production success-producing seam is added.
    function preg_match(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int|false
    {
        if ((Tests\ValidationFault::$remaining > 0 || Tests\ValidationFault::$throw)
            && (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? null) === ManifestValidator::class) {
            if (Tests\ValidationFault::$throw) {
                throw new \LogicException('Synthetic validator programming defect.');
            }
            Tests\ValidationFault::$remaining--;

            return 0;
        }

        return \preg_match($pattern, $subject, $matches, $flags, $offset);
    }
}

namespace RmaTransactionBoundaryPoc\Integrity\Tests {

    use Closure;
    use InvalidArgumentException;
    use LogicException;
    use ReflectionClass;
    use RmaTransactionBoundaryPoc\Integrity\AnchorProvider;
    use RmaTransactionBoundaryPoc\Integrity\CanonicalJson;
    use RmaTransactionBoundaryPoc\Integrity\Manifest;
    use RmaTransactionBoundaryPoc\Integrity\ManifestDigestVerifier;
    use RmaTransactionBoundaryPoc\Integrity\ManifestValidator;
    use RmaTransactionBoundaryPoc\Integrity\ManifestVerificationResult;
    use RmaTransactionBoundaryPoc\Integrity\StrictJsonTokenizer;
    use RmaTransactionBoundaryPoc\Integrity\TrustAnchor;
    use RmaTransactionBoundaryPoc\Integrity\TrustContext;
    use RuntimeException;
    use Throwable;

    final class ValidationFault
    {
        public static int $remaining = 0;

        public static bool $throw = false;
    }

    final class VerificationFileTrap
    {
        public mixed $context;

        public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
        {
            throw new LogicException('Unexpected file access during pure verification.');
        }

        public function url_stat(string $path, int $flags): array|false
        {
            throw new LogicException('Unexpected file stat during pure verification.');
        }

        public function dir_opendir(string $path, int $options): bool
        {
            throw new LogicException('Unexpected directory access during pure verification.');
        }
    }

    final class ManifestVerificationTest
    {
        private const R1_ORACLE = [300379, '54c1a6920a02c0394abdbce83ce9064c3acb835bbd05235ebc35071ffe64568c'];

        public static int $assertions = 0;

        public static int $resultsChecked = 0;

        // Independent TD03 byte recipes and pinned SHA references, not CanonicalJson output.
        // The leading-space reference was separately calculated with Python hashlib.
        private const ORACLES = [
            'small' => [646, 'c7c49ce3bc5120712956843f36e037614c06aed1901e35b0d67ead7a2bdd31b7'],
            'multi' => [1050, '351e8caebefdcfe7f19b7af109475dadfcc1b98bb04879bc6f042bc2cc918678'],
            'byte-heavy' => [14761136, '9c6c23d1d8572c1dbce649b9267189b28eaf9d7e40b450969419cf03c203c74a'],
            'entry-heavy' => [13700372, '036b460039867b63da67298b803f193349a168aeff8576b27fc9c478df4c58e4'],
            'wire-max' => [16777216, '81af27220c351c06526a05175e9f3430c7af389364e91f94f9836d8c7de2378e'],
            'noncanonical-large' => [14761137, '1d97cb5f7e9976b4423199cf3e581a5361aec635cdf1db760ad1a0a2e49b1801'],
        ];

        public static function cases(): array
        {
            $cases = [];
            $cases['TD04 G01 unavailable hash_algos child runtime'] = static function (): void {
                // Real disabled-function runtime, with an autoload barrier before
                // anchor validation and every subsequent verification component.
                $script = <<<'PHP'
                namespace RmaTransactionBoundaryPoc\Integrity;
                $src = $argv[1];
                foreach (['TrustAnchor', 'TrustContext', 'AnchorProvider', 'DigestComparisonResult', 'TrustedDigestVerifier', 'ManifestDigestVerifier', 'ManifestVerificationResult'] as $class) {
                    require $src.'/'.$class.'.php';
                }
                $assertions = 0;
                $check = static function (bool $condition) use (&$assertions): void {
                    $assertions++;
                    if (! $condition) {
                        throw new \RuntimeException('Isolated G01 assertion '.$assertions.' failed.');
                    }
                };
                $check(PHP_VERSION_ID >= 80400 && PHP_INT_SIZE === 8);
                $check(ini_get('disable_functions') === 'hash_algos');
                $check(\function_exists('hash') && \function_exists('hash_equals') && ! \function_exists('hash_algos'));
                $raw = '{}';
                $anchor = [
                    'anchorVersion' => '1.0', 'anchorId' => 'anchor-1', 'manifestId' => 'm',
                    'manifestSha256' => \hash('sha256', $raw), 'profileId' => 'q',
                    'profileSha256' => str_repeat('a', 64), 'approvalRef' => 'approval-1',
                    'verifierReleaseId' => 'release-1', 'status' => 'ACTIVE',
                ];
                $context = new TrustContext(...(['trustMode' => 'FIXTURE', 'selectionOrigin' => 'independent-test', 'selectionRef' => 'review-1'] + $anchor));
                $verifier = new ManifestDigestVerifier(new AnchorProvider(new TrustAnchor(...$anchor)));
                $laterLoads = 0;
                spl_autoload_register(static function (string $class) use (&$laterLoads): never {
                    $laterLoads++;
                    throw new \LogicException('Later verification component executed: '.$class);
                });
                $result = $verifier->verify($context, (object) $anchor, $raw);
                $check($result->verdict === 'INCONCLUSIVE');
                $check($result->primaryFailureCode === 'CAPABILITY_UNAVAILABLE');
                $check($result->manifest === null);
                $check($result->scope === 'MANIFEST_ONLY');
                $check($result->anchorHistoryStatus === 'NOT_EVALUATED');
                $check($result->fileInventoryStatus === 'NOT_EVALUATED');
                $check($result->filesystemIdentityStatus === 'NOT_EVALUATED');
                $check($result->executionAuthorization === 'NOT_EVALUATED');
                $check($result->canLaunchPoC === false);
                $check($result->errors[0]['stage'] === 'G01');
                $check($result->digestStatus === 'NOT_EVALUATED' && $result->actualDigest === null);
                $check($result->schemaStatus === 'NOT_EVALUATED' && $result->canonicalStatus === 'NOT_EVALUATED' && $result->bindingStatus === 'NOT_EVALUATED');
                $check($laterLoads === 0);
                echo json_encode(['assertions' => $assertions, 'laterComponentLoads' => $laterLoads], JSON_THROW_ON_ERROR);
                PHP;
                $command = [PHP_BINARY, '-n', '-d', 'disable_functions=hash_algos', '-r', $script, dirname(__DIR__).'/src'];
                $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                self::ensure(is_resource($process));
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                self::ensure($exit === 0 && $stderr === '');
                $report = json_decode($stdout, true, 16, JSON_THROW_ON_ERROR);
                self::ensure($report === ['assertions' => 16, 'laterComponentLoads' => 0]);
                self::$assertions += $report['assertions'];
            };
            foreach (['FIXTURE', 'OPERATOR_ATTESTED'] as $mode) {
                $cases['TD04 complete '.$mode] = static function () use ($mode): void {
                    $raw = self::fixture('multi');
                    [$v, $context, $candidate] = self::setup($raw, [], ['trustMode' => $mode]);
                    $result = self::checked($v, $context, $candidate, $raw);
                    self::ensure($result->manifestVerdict === 'VERIFIED' && $result->primaryFailureCode === null);
                    self::ensure($result->digestStatus === 'MATCH' && $result->schemaStatus === 'VALID');
                    self::ensure($result->canonicalStatus === 'CANONICAL' && $result->bindingStatus === 'MATCH');
                    self::ensure($result->authenticity === ($mode === 'FIXTURE' ? 'SIMULATED' : $mode));
                    self::ensure($result->actualDigest === self::ORACLES['multi'][1] && $result->manifest->files[1]['path'] === 'p/000001');
                    self::ensure($result->errors === [] && ! $result->diagnosticsTruncated);
                };
            }
            foreach ([
                ['context type', null, 'CONFIG_INVALID'],
                ['invalid mode', ['trustMode' => 'AUTO'], 'CONFIG_INVALID'],
                ['blank selection', ['selectionRef' => ' '], 'CONFIG_INVALID'],
                ['invalid expected tuple', ['manifestSha256' => 'BAD'], 'CONFIG_INVALID'],
                ['deployment', ['trustMode' => 'DEPLOYMENT'], 'CAPABILITY_UNAVAILABLE'],
                ['protected', ['trustMode' => 'PROTECTED_DEPLOYMENT'], 'CAPABILITY_UNAVAILABLE'],
            ] as [$name, $contextChanges, $code]) {
                $cases['TD04 G01 '.$name] = static function () use ($contextChanges, $code): void {
                    $raw = self::fixture();
                    [$v, $context, $candidate] = self::setup($raw, [], $contextChanges ?? []);
                    $r = self::checked($v, $contextChanges === null ? null : $context, $candidate, $raw);
                    self::failed($r, $code, 'G01');
                    self::ensure($r->verdict === ($code === 'CAPABILITY_UNAVAILABLE' ? 'INCONCLUSIVE' : 'DENIED'));
                    self::ensure(DigestCalls::$hashCalls === 0 && $r->anchorId === null && $r->authenticity === null);
                };
            }
            foreach ([
                ['missing', null, 'ANCHOR_MISSING'], ['type', 'raw anchor JSON', 'ANCHOR_INVALID'],
                ['version', ['anchorVersion' => '2.0'], 'ANCHOR_VERSION_UNSUPPORTED'],
                ['revoked', ['status' => 'REVOKED'], 'ANCHOR_REVOKED'],
                ['unknown status', ['status' => 'active'], 'ANCHOR_INVALID'],
                ['substitution', ['approvalRef' => 'other'], 'ANCHOR_SELECTION_MISMATCH'],
                ['profile', ['profileId' => 'other'], 'PROFILE_MISMATCH'],
                ['profile hash', ['profileSha256' => str_repeat('c', 64)], 'PROFILE_MISMATCH'],
                ['release', ['verifierReleaseId' => 'other'], 'VERIFIER_RELEASE_MISMATCH'],
                ['extra', ['extra' => 'x'], 'ANCHOR_INVALID'],
            ] as [$name, $changes, $code]) {
                $cases['TD04 G02 '.$name] = static function () use ($changes, $code): void {
                    $raw = self::fixture();
                    [$v, $context, $candidate] = self::setup($raw);
                    $candidate = is_array($changes) ? (object) array_replace(get_object_vars($candidate), $changes) : $changes;
                    self::failed(self::checked($v, $context, $candidate, $raw), $code, 'G02');
                    self::ensure(DigestCalls::$hashCalls === 0);
                };
            }
            $cases['TD04 missing selected anchor'] = static function (): void {
                $raw = self::fixture();
                [, $context, $candidate] = self::setup($raw);
                self::failed(self::checked(new ManifestDigestVerifier(new AnchorProvider(null)), $context, $candidate, $raw), 'ANCHOR_MISSING', 'G02');
            };
            foreach ([
                ['grammar', '{', 'JSON_INVALID'], ['duplicate', '{"a":0,"\u0061":1}', 'JSON_DUPLICATE_KEY'],
                ['encoding', "\xef\xbb\xbf{}", 'ENCODING_INVALID'], ['ASCII domain', '"\u00e9"', 'JSON_NON_ASCII'],
                ['key', '{"\u0000":0}', 'JSON_KEY_INVALID'], ['number', '-1', 'JSON_NUMBER_INVALID'],
                ['overflow', '9223372036854775808', 'JSON_INTEGER_OVERFLOW'],
                ['depth', str_repeat('[', 17).'0'.str_repeat(']', 17), 'JSON_DEPTH_EXCEEDED'],
                ['complexity', '"'.str_repeat('a', 241).'"', 'JSON_COMPLEXITY_LIMIT'],
            ] as [$name, $raw, $code]) {
                $cases['TD04 G05 '.$name] = static function () use ($raw, $code): void {
                    [$v, $context, $candidate] = self::setup($raw);
                    $r = self::checked($v, $context, $candidate, $raw);
                    self::failed($r, $code, 'G05');
                    self::ensure($r->digestStatus === 'MATCH' && $r->schemaStatus === 'NOT_EVALUATED');
                };
            }
            foreach ([
                ['null', '"manifestId":"m"', '"manifestId":null', 'TYPE_INVALID'],
                ['timestamp', '2026-11-18T00:00:00Z', '2026-02-30T00:00:00Z', 'TIMESTAMP_INVALID'],
                ['size', '"sizeBytes":0', '"sizeBytes":2147483649', 'SIZE_INVALID'],
                ['path', 'p/000000', '../x', 'PATH_INVALID'],
                ['unknown', '"category":"documentation"', '"category":"documentation","extra":0', 'FIELD_UNKNOWN'],
            ] as [$name, $from, $to, $code]) {
                $cases['TD04 G06 '.$name] = static function () use ($from, $to, $code): void {
                    $raw = str_replace($from, $to, self::fixture());
                    [$v, $context, $candidate] = self::setup($raw);
                    $r = self::checked($v, $context, $candidate, $raw);
                    self::failed($r, 'MANIFEST_SCHEMA_INVALID', 'V06_V07');
                    self::ensure(in_array($code, array_column($r->errors, 'code'), true));
                    self::ensure($r->schemaStatus === 'INVALID' && $r->canonicalStatus === 'NOT_EVALUATED');
                };
            }
            foreach (['order' => 'INVENTORY_ORDER_INVALID', 'case' => 'CASE_COLLISION', 'overlap' => 'DUPLICATE_PATH'] as $kind => $code) {
                $cases['TD04 imported inventory '.$kind] = static function () use ($kind, $code): void {
                    $data = \json_decode(self::fixture('multi'), false, 16, JSON_THROW_ON_ERROR);
                    if ($kind === 'order') {
                        $data->files = array_reverse($data->files);
                    } elseif ($kind === 'case') {
                        $data->files[1]->path = strtoupper($data->files[0]->path);
                    } else {
                        $data->historicalArtifacts[0]->path = $data->files[0]->path;
                    }
                    $raw = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
                    [$v, $context, $candidate] = self::setup($raw);
                    $r = self::checked($v, $context, $candidate, $raw);
                    self::failed($r, 'MANIFEST_SCHEMA_INVALID', 'V06_V07');
                    self::ensure(in_array($code, array_column($r->errors, 'code'), true));
                    self::ensure($r->canonicalStatus === 'NOT_EVALUATED');
                };
            }
            foreach ([99, 100, 101] as $count) {
                $cases['TD04 diagnostic budget '.$count] = static function () use ($count): void {
                    $raw = self::diagnosticRaw($count);
                    [$v, $context, $candidate] = self::setup($raw);
                    $r = self::checked($v, $context, $candidate, $raw);
                    self::failed($r, 'MANIFEST_SCHEMA_INVALID', 'V06_V07');
                    self::ensure(count($r->errors) === min($count, 100) && $r->diagnosticsTruncated === ($count >= 100));
                    $reference = (new ManifestValidator)->validate((new StrictJsonTokenizer)->parse($raw)->value);
                    self::ensure($r->errors === array_map(static fn ($e) => ['stage' => 'V06_V07'] + $e, $reference->errors));
                    self::ensure($r->diagnosticsTruncated === $reference->diagnosticsTruncated);
                };
            }
            $base = self::fixture();
            foreach ([
                'missing LF' => substr($base, 0, -1), 'double LF' => $base."\n", 'CRLF' => substr($base, 0, -1)."\r\n",
                'leading space' => ' '.$base, 'pretty' => str_replace('":', '": ', $base),
                'key order' => str_replace('"createdAt":"2026-11-18T00:00:00Z","exampleOnly":false', '"exampleOnly":false,"createdAt":"2026-11-18T00:00:00Z"', $base),
                'slash escape' => str_replace('p/', 'p\\/', $base), 'ASCII escape' => str_replace('"manifestId":"m"', '"manifestId":"\u006d"', $base),
            ] as $name => $raw) {
                $cases['TD04 G07 '.$name] = static function () use ($raw): void {
                    [$v, $context, $candidate] = self::setup($raw);
                    $r = self::checked($v, $context, $candidate, $raw);
                    self::failed($r, 'MANIFEST_NONCANONICAL', 'G07');
                    self::ensure($r->schemaStatus === 'VALID' && $r->canonicalStatus === 'NONCANONICAL');
                    self::ensure($r->bindingStatus === 'NOT_EVALUATED' && $r->actualDigest === \hash('sha256', $raw));
                };
            }
            foreach ([['manifestId', 'different', 'ANCHOR_MANIFEST_MISMATCH'], ['profileId', 'other', 'PROFILE_MISMATCH'], ['profileSha256', str_repeat('c', 64), 'PROFILE_MISMATCH']] as [$field, $value, $code]) {
                $cases['TD04 G08 '.$field] = static function () use ($field, $value, $code): void {
                    $raw = self::fixture();
                    [$v, $context, $candidate] = self::setup($raw, [$field => $value]);
                    $r = self::checked($v, $context, $candidate, $raw);
                    self::failed($r, $code, 'G08');
                    self::ensure($r->canonicalStatus === 'CANONICAL' && $r->bindingStatus === 'MISMATCH');
                };
            }
            $cases['TD04 precedence and exact raw boundaries'] = static function (): void {
                $oversize = str_repeat(' ', 16777217);
                [$v, $context, $candidate] = self::setup($oversize, ['manifestSha256' => str_repeat('0', 64)]);
                $bad = clone $candidate;
                $bad->status = 'REVOKED';
                self::failed(self::checked($v, $context, $bad, $oversize), 'ANCHOR_REVOKED', 'G02');
                self::ensure(DigestCalls::$hashCalls === 0);
                self::failed(self::checked($v, $context, $candidate, $oversize), 'MANIFEST_SIZE_EXCEEDED', 'G03');
                self::ensure(DigestCalls::$hashCalls === 0);
                unset($oversize);
                $raw = self::fixture();
                [$v, $context, $candidate] = self::setup($raw);
                foreach (['{', $raw."\0", substr($raw, 0, -1)."\r\n", $raw."\xff"] as $changed) {
                    $r = self::checked($v, $context, $candidate, $changed);
                    self::failed($r, 'MANIFEST_UNTRUSTED', 'G04');
                    self::ensure($r->digestStatus === 'MISMATCH' && $r->actualDigest === \hash('sha256', $changed));
                }
                foreach ([['{', 'JSON_INVALID'], [' '.str_replace('"manifestId":"m"', '"manifestId":null', $raw), 'MANIFEST_SCHEMA_INVALID'], [' '.$raw, 'MANIFEST_NONCANONICAL']] as [$input, $code]) {
                    [$v, $context, $candidate] = self::setup($input, ['manifestId' => 'other']);
                    self::ensure(self::checked($v, $context, $candidate, $input)->primaryFailureCode === $code);
                }
                $exact = self::fixture('wire-max');
                self::ensure(strlen($exact) === 16777216);
                [$v, $context, $candidate] = self::setup($exact);
                self::ensure(self::checked($v, $context, $candidate, $exact)->verdict === 'VERIFIED');
            };
            $cases['TD04 lifecycle one verifier and fixed pin'] = static function (): void {
                $valid = self::fixture('entry-heavy');
                [$v, $context, $candidate] = self::setup($valid);
                self::ensure(self::checked($v, $context, $candidate, $valid)->verdict === 'VERIFIED');
                foreach ([100, 2] as $n) {
                    ValidationFault::$remaining = $n;
                    try {
                        $r = self::checked($v, $context, $candidate, $valid);
                        self::failed($r, 'MANIFEST_SCHEMA_INVALID', 'V06_V07');
                        self::ensure(count($r->errors) === $n && $r->diagnosticsTruncated === ($n === 100));
                    } finally {
                        ValidationFault::$remaining = 0;
                    }
                    self::ensure(self::checked($v, $context, $candidate, $valid)->verdict === 'VERIFIED');
                }
            };
            $cases['TD04 construction immutability and hydration'] = static function (): void {
                $raw = self::fixture();
                [$v, $context, $candidate] = self::setup($raw);
                $r = self::checked($v, $context, $candidate, $raw);
                $reflection = new ReflectionClass(ManifestVerificationResult::class);
                self::ensure($reflection->isFinal() && $reflection->isReadOnly() && $reflection->getConstructor()->isPrivate());
                self::reject(static fn () => new ManifestVerificationResult, \Error::class);
                self::reject(static function () use ($r): void {
                    $r->canLaunchPoC = true;
                }, \Error::class);
                self::reject(static function () use ($r): void {
                    $r->manifest->files[0]['sizeBytes'] = 1;
                }, \Error::class);
                self::reject(static fn () => unserialize(serialize($r)), InvalidArgumentException::class);
                $payload = serialize((object) ['manifestVerdict' => 'VERIFIED', 'canLaunchPoC' => true]);
                $forged = 'O:'.strlen(ManifestVerificationResult::class).':"'.ManifestVerificationResult::class.'"'.substr($payload, strlen('O:8:"stdClass"'));
                self::reject(static fn () => unserialize($forged, ['allowed_classes' => [ManifestVerificationResult::class]]), InvalidArgumentException::class);
                $facts = $v->evaluate($context, $candidate, $raw);
                $facts['canLaunchPoC'] = true;
                self::ensure(ManifestVerificationResult::fromVerification($v, $context, $candidate, '{')->verdict === 'DENIED');
                self::ensure(! array_key_exists('rawBytes', $r->toArray()) && $r->manifest instanceof Manifest);
            };
            $cases['TD04 pure verification has no file access or new includes'] = static function (): void {
                $raw = self::fixture();
                [$v, $context, $candidate] = self::setup($raw);
                $before = get_included_files();
                self::ensure(stream_wrapper_unregister('file'));
                try {
                    self::ensure(stream_wrapper_register('file', VerificationFileTrap::class));
                    self::ensure(self::checked($v, $context, $candidate, $raw)->verdict === 'VERIFIED');
                    self::failed(self::checked($v, $context, $candidate, '{'), 'MANIFEST_UNTRUSTED', 'G04');
                } finally {
                    stream_wrapper_restore('file');
                }
                self::ensure(get_included_files() === $before);
            };
            $cases['TD04 programming exception propagates'] = static function (): void {
                $raw = self::fixture();
                [$v, $context, $candidate] = self::setup($raw);
                DigestCalls::reset();
                DigestCalls::$throwOnHash = true;
                try {
                    self::reject(static fn () => $v->verify($context, $candidate, $raw), LogicException::class);
                } finally {
                    DigestCalls::reset();
                }
                ValidationFault::$throw = true;
                try {
                    self::reject(static fn () => $v->verify($context, $candidate, $raw), LogicException::class);
                } finally {
                    ValidationFault::$throw = false;
                }
                self::ensure(self::checked($v, $context, $candidate, $raw)->verdict === 'VERIFIED');
            };
            $cases['TD04 resource observer rejects invalid and late evidence'] = static function (): void {
                // Synthetic predicate inputs only; actual children use memory_get_peak_usage.
                $record = [
                    'scenario' => 'small', 'php' => PHP_VERSION, 'integerBytes' => 8, 'memoryLimit' => '512M',
                    'rawBytes' => self::ORACLES['small'][0], 'actualDigest' => self::ORACLES['small'][1],
                    'result' => ['verdict' => 'VERIFIED', 'code' => null, 'errors' => 0, 'truncated' => false],
                    'peakUsed' => 1, 'peakAllocated' => 2,
                ];
                $start = hrtime(true);
                self::ensure(self::memoryAccepted($record, 0, false, '', $start, 'small', '512M'));
                foreach ([30, 31, 12000] as $seconds) {
                    self::ensure(! self::memoryAccepted($record, 0, false, '', $start - $seconds * 1000000000, 'small', '512M'));
                }
                foreach ([[null, 0, false, ''], [$record, 1, false, ''], [$record, -1, false, ''], [$record, 0, true, ''], [$record, 0, false, 'fatal']] as [$r, $exit, $timeout, $stderr]) {
                    self::ensure(! self::memoryAccepted($r, $exit, $timeout, $stderr, $start, 'small', '512M'));
                }
                foreach (['scenario', 'php', 'rawBytes', 'actualDigest', 'peakUsed', 'peakAllocated', 'result'] as $key) {
                    $bad = $record;
                    unset($bad[$key]);
                    self::ensure(! self::memoryAccepted($bad, 0, false, '', $start, 'small', '512M'));
                }
                $record['result']['verdict'] = 'DENIED';
                self::ensure(! self::memoryAccepted($record, 0, false, '', $start, 'small', '512M'));
            };

            return $cases;
        }

        private static function setup(string $raw, array $anchorChanges = [], array $contextChanges = []): array
        {
            $long = str_contains($raw, '"profileId":"'.str_repeat('q', 80).'"');
            $a = array_replace([
                'anchorVersion' => '1.0', 'anchorId' => 'anchor-1', 'manifestId' => $long ? str_repeat('m', 80) : 'm',
                'manifestSha256' => \hash('sha256', $raw), 'profileId' => $long ? str_repeat('q', 80) : 'q',
                'profileSha256' => str_repeat('a', 64), 'approvalRef' => 'approval-1', 'verifierReleaseId' => 'release-1', 'status' => 'ACTIVE',
            ], $anchorChanges);
            $context = new TrustContext(...array_replace(['trustMode' => 'FIXTURE', 'selectionOrigin' => 'independent-test', 'selectionRef' => 'review-1'] + $a, $contextChanges));

            return [new ManifestDigestVerifier(new AnchorProvider(new TrustAnchor(...$a))), $context, (object) $a];
        }

        private static function checked(ManifestDigestVerifier $v, mixed $context, mixed $candidate, string $raw): ManifestVerificationResult
        {
            DigestCalls::reset();
            $before = \hash('sha256', $raw);
            $r = $v->verify($context, $candidate, $raw);
            self::ensure(\hash('sha256', $raw) === $before);
            self::invariants($r);

            return $r;
        }

        private static function invariants(ManifestVerificationResult $r): void
        {
            self::$resultsChecked++;
            self::ensure($r->scope === 'MANIFEST_ONLY' && $r->resultVersion === '1.0');
            self::ensure($r->fileInventoryStatus === 'NOT_EVALUATED' && $r->filesystemIdentityStatus === 'NOT_EVALUATED');
            self::ensure($r->executionAuthorization === 'NOT_EVALUATED' && $r->canLaunchPoC === false);
            self::ensure($r->anchorHistoryStatus === 'NOT_EVALUATED' && $r->verdict === $r->manifestVerdict);
            self::ensure(($r->verdict === 'VERIFIED') === ($r->manifest instanceof Manifest));
            self::ensure(count($r->errors) <= 100);
            $errors = $r->errors;
            usort($errors, static fn ($a, $b) => strcmp($a['stage'], $b['stage']) ?: strcmp($a['path'], $b['path']) ?: strcmp($a['code'], $b['code']) ?: strcmp($a['message'], $b['message']));
            self::ensure($errors === $r->errors);
        }

        private static function failed(ManifestVerificationResult $r, string $code, string $stage): void
        {
            self::ensure($r->primaryFailureCode === $code && $r->manifest === null);
            self::ensure($r->errors !== [] && $r->errors[0]['stage'] === $stage);
        }

        private static function ensure(bool $condition): void
        {
            self::$assertions++;
            if (! $condition) {
                throw new RuntimeException('TD04 assertion '.self::$assertions.' failed.');
            }
        }

        private static function reject(Closure $call, string $type): void
        {
            $caught = false;
            try {
                $call();
            } catch (Throwable $e) {
                $caught = $e instanceof $type;
            }
            self::ensure($caught);
        }

        private static function diagnosticRaw(int $n): string
        {
            $data = \json_decode(self::fixture(), false, 16, JSON_THROW_ON_ERROR);
            $data->files = array_fill(0, intdiv($n, 4), (object) []);
            if ($n % 4 === 3) {
                $data->files[] = (object) ['category' => 'documentation'];
            } elseif ($n % 4 === 1) {
                $data->files[] = (object) ['category' => 'documentation', 'path' => 'p/last', 'sizeBytes' => 0];
            }

            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        }

        private static function fixture(string $id = 'small'): string
        {
            $wire = $id === 'wire-max';
            $long = $wire || in_array($id, ['byte-heavy', 'noncanonical-large'], true);
            [$files, $history] = match ($id) {
                'small' => [1, 1], 'multi' => [3, 2], 'byte-heavy', 'noncanonical-large' => [40000, 1],
                'entry-heavy', 'r1' => [99999, 1], 'wire-max' => [1, 38042],
                default => throw new RuntimeException('Unknown TD04 fixture.'),
            };
            $idLength = $wire || ! $long ? 1 : 80;
            $raw = ($id === 'noncanonical-large' ? ' ' : '').'{"createdAt":"2026-11-18T00:00:00Z","exampleOnly":false,"files":[';
            for ($i = 0; $i < $files; $i++) {
                $path = 'p/'.($long ? str_repeat('a', 232) : '').sprintf('%06d', $i);
                $raw .= ($i === 0 ? '' : ',').($id === 'r1' ? '{}' : '{"category":"documentation","path":"'.$path.'","sha256":"'.str_repeat('a', 64).'","sizeBytes":0}');
            }
            $raw .= '],"formatId":"rma-poc-inputs","historicalArtifacts":[';
            for ($i = 0; $i < $history; $i++) {
                $path = 'h/'.($long ? str_repeat('a', 232) : '').sprintf('%06d', $i);
                $ref = str_repeat('r', $wire ? ($i === 0 ? 26 : 80) : $idLength);
                $raw .= ($i === 0 ? '' : ',').($id === 'r1' ? '{}' : '{"path":"'.$path.'","provenanceRef":"'.$ref.'","sha256":"'.str_repeat('a', 64).'","sizeBytes":0}');
            }

            return $raw.'],"manifestId":"'.str_repeat('m', $idLength).'","pocVersion":"'.str_repeat('v', $idLength).'","schemaVersion":"1.0","verificationMetadata":{"baselineCommit":"'.str_repeat('b', 40).'","profileId":"'.str_repeat('q', $idLength).'","profileSha256":"'.str_repeat('a', 64).'","provenanceRef":"'.str_repeat('r', $idLength).'"}}'."\n";
        }

        public static function observe(): int
        {
            if (! function_exists('xdebug_start_function_monitor') || ! in_array('develop', xdebug_info('mode'), true)) {
                throw new RuntimeException('TD04 observation requires Xdebug develop mode.');
            }
            $valid = self::fixture();
            foreach ([
                ['digest mismatch', '{', $valid, [0, 0, 0, 0]],
                ['parser failure', '{', '{', [1, 0, 0, 0]],
                ['schema failure', 'null', 'null', [1, 1, 0, 0]],
                ['success', $valid, $valid, [1, 1, 1, 1]],
            ] as [$name, $raw, $pinned, $expected]) {
                [$v, $context, $candidate] = self::setup($pinned);
                $methods = [StrictJsonTokenizer::class.'::parse', ManifestValidator::class.'::validate', CanonicalJson::class.'::isCanonical', CanonicalJson::class.'::serialize'];
                xdebug_start_function_monitor([StrictJsonTokenizer::class.'->parse', ManifestValidator::class.'->validate', CanonicalJson::class.'::isCanonical', CanonicalJson::class.'::serialize']);
                $start = count(xdebug_get_monitored_functions());
                try {
                    $r = self::checked($v, $context, $candidate, $raw);
                    $events = array_slice(xdebug_get_monitored_functions(), $start);
                } finally {
                    xdebug_stop_function_monitor();
                }
                $actual = array_fill(0, 4, 0);
                foreach ($events as $event) {
                    $index = array_search(str_replace('->', '::', ltrim($event['function'], '\\')), $methods, true);
                    if ($index !== false) {
                        $actual[$index]++;
                    }
                }
                self::ensure($actual === $expected);
                echo json_encode(['scenario' => $name, 'methods' => $methods, 'counts' => $actual, 'verdict' => $r->verdict], JSON_THROW_ON_ERROR).PHP_EOL;
            }

            return 0;
        }

        public static function memoryIds(): array
        {
            return array_keys(self::ORACLES);
        }

        public static function memoryCase(string $id): array
        {
            $raw = self::fixture($id);
            $sha = \hash('sha256', $raw);
            self::ensure([strlen($raw), $sha] === ($id === 'r1' ? self::R1_ORACLE : self::ORACLES[$id]));
            [$v, $context, $candidate] = self::setup($raw);
            $r = self::checked($v, $context, $candidate, $raw);
            $expectedCode = $id === 'r1' ? 'MANIFEST_SCHEMA_INVALID' : ($id === 'noncanonical-large' ? 'MANIFEST_NONCANONICAL' : null);
            self::ensure($r->primaryFailureCode === $expectedCode);
            self::ensure($r->actualDigest === $sha && $r->expectedDigest === $sha);
            self::ensure($r->verdict === ($expectedCode === null ? 'VERIFIED' : 'DENIED'));
            if ($id === 'r1') {
                self::ensure(count($r->errors) === 100 && $r->diagnosticsTruncated && $r->manifest === null);
            }

            return [
                'scenario' => $id, 'php' => PHP_VERSION, 'integerBytes' => PHP_INT_SIZE, 'memoryLimit' => ini_get('memory_limit'),
                'rawBytes' => strlen($raw), 'actualDigest' => $r->actualDigest,
                'result' => ['verdict' => $r->verdict, 'code' => $r->primaryFailureCode, 'errors' => count($r->errors), 'truncated' => $r->diagnosticsTruncated],
                'peakUsed' => memory_get_peak_usage(false), 'peakAllocated' => memory_get_peak_usage(true),
            ];
        }

        public static function memorySuite(string $runner, bool $r1 = false): int
        {
            $runs = 0;
            $signatures = [];
            foreach ($r1 ? ['r1'] : self::memoryIds() as $id) {
                for ($repeat = 1; $repeat <= ($r1 ? 1 : 3); $repeat++) {
                    $pipes = [];
                    $limit = $r1 ? '128M' : '512M';
                    $command = [PHP_BINARY, '-n', '-d', 'memory_limit='.$limit, $runner, '--td04-memory-case='.$id];
                    $start = hrtime(true);
                    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]);
                    if (! is_resource($process)) {
                        throw new RuntimeException('Cannot start TD04 memory child.');
                    }
                    fclose($pipes[0]);
                    stream_set_blocking($pipes[1], false);
                    stream_set_blocking($pipes[2], false);
                    $stdout = $stderr = '';
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
                                throw new RuntimeException('Cannot terminate TD04 memory child.');
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
                    $signature = is_array($record) ? [$record['rawBytes'] ?? null, $record['actualDigest'] ?? null, $record['result'] ?? null] : null;
                    $repeatable = ! isset($signatures[$id]) || $signatures[$id] === $signature;
                    $signatures[$id] = $signature;
                    $ok = self::memoryAccepted($record, $exit, $timeout, $stderr, $start, $id, $limit, $elapsed) && $repeatable;
                    $runs++;
                    echo json_encode(['repeat' => $repeat, 'exit' => $exit, 'timeout' => $timeout || $elapsed >= 30, 'pass' => $ok, 'elapsedSeconds' => round($elapsed, 3), 'record' => $record, 'stderr' => $stderr], JSON_THROW_ON_ERROR).PHP_EOL;
                    if (! $ok) {
                        return 1;
                    }
                }
            }
            echo 'TD04 '.($r1 ? 'R1' : 'memory').': '.$runs.' runs passed.'.PHP_EOL;

            return 0;
        }

        private static function memoryAccepted(mixed $record, int $exit, bool $timeout, string $stderr, int $start, string $id, string $limit, ?float &$elapsed = null): bool
        {
            $code = $id === 'r1' ? 'MANIFEST_SCHEMA_INVALID' : ($id === 'noncanonical-large' ? 'MANIFEST_NONCANONICAL' : null);
            $expected = ['verdict' => $code === null ? 'VERIFIED' : 'DENIED', 'code' => $code, 'errors' => $id === 'r1' ? 100 : ($code === null ? 0 : 1), 'truncated' => $id === 'r1'];
            $valid = ! $timeout && $exit === 0 && $stderr === '' && is_array($record)
                && ($record['scenario'] ?? null) === $id
                && is_string($record['php'] ?? null) && version_compare($record['php'], '8.4.0', '>=')
                && ($record['integerBytes'] ?? null) === 8 && ($record['memoryLimit'] ?? null) === $limit
                && ($record['result'] ?? null) === $expected
                && is_int($record['peakUsed'] ?? null) && is_int($record['peakAllocated'] ?? null)
                && $record['peakUsed'] > 0 && $record['peakUsed'] <= $record['peakAllocated']
                && $record['peakAllocated'] <= ($limit === '128M' ? 134217728 : 536870912)
                && [$record['rawBytes'] ?? null, $record['actualDigest'] ?? null] === ($id === 'r1' ? self::R1_ORACLE : self::ORACLES[$id]);
            $elapsed = (hrtime(true) - $start) / 1e9;

            return $valid && $elapsed >= 0 && $elapsed < 30;
        }
    }
}
