<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity {
    // Namespaced test doubles delegate to PHP primitives; production has no injected hash hook.
    function hash(string $algorithm, string $data, bool $binary = false): string
    {
        Tests\DigestCalls::$hashCalls++;
        Tests\DigestCalls::$hashedLength = strlen($data);
        if (Tests\DigestCalls::$throwOnHash) {
            throw new \LogicException('Synthetic hash programming defect.');
        }

        return \hash($algorithm, $data, $binary);
    }

    function hash_equals(string $known, string $actual): bool
    {
        Tests\DigestCalls::$comparisons[] = [$known, $actual];
        if (Tests\DigestCalls::$hashCalls > 0) {
            Tests\DigestCalls::$digestComparisons++;
        }

        return \hash_equals($known, $actual);
    }

    function json_decode(string $json): never
    {
        Tests\DigestCalls::$parserCalls++;
        throw new \LogicException('TD01 must not parse manifest or raw anchor JSON.');
    }
}

namespace RmaTransactionBoundaryPoc\Integrity\Tests {
    use Closure;
    use Error;
    use LogicException;
    use ReflectionClass;
    use RmaTransactionBoundaryPoc\Integrity\AnchorProvider;
    use RmaTransactionBoundaryPoc\Integrity\DigestComparisonResult;
    use RmaTransactionBoundaryPoc\Integrity\TrustAnchor;
    use RmaTransactionBoundaryPoc\Integrity\TrustContext;
    use RmaTransactionBoundaryPoc\Integrity\TrustedDigestVerifier;
    use RuntimeException;
    use stdClass;

    final class DigestCalls
    {
        public static int $hashCalls = 0;

        public static int $hashedLength = 0;

        public static int $parserCalls = 0;

        public static int $digestComparisons = 0;

        public static bool $throwOnHash = false;

        public static array $comparisons = [];

        public static function reset(): void
        {
            self::$hashCalls = 0;
            self::$hashedLength = 0;
            self::$parserCalls = 0;
            self::$digestComparisons = 0;
            self::$throwOnHash = false;
            self::$comparisons = [];
        }
    }

    final class TrustVerificationTest
    {
        private const ABC_SHA = 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad';

        public static int $assertions = 0;

        public static int $resultsChecked = 0;

        /** @return array<string, Closure(): void> */
        public static function cases(): array
        {
            $cases = [];
            $cases['TD01 known-answer SHA-256 abc and exact fixture tuple'] = static function (): void {
                $result = self::verify(self::context(), self::record(), 'abc');
                self::ensure($result->phaseVerdict === 'MATCH' && $result->digestStatus === 'MATCH');
                self::ensure($result->primaryFailureCode === null && $result->errors === []);
                self::ensure($result->expectedDigest === self::ABC_SHA && $result->actualDigest === self::ABC_SHA);
                self::ensure($result->trustMode === 'FIXTURE' && $result->authenticity === 'SIMULATED');
                self::ensure($result->anchorId === 'anchor-1' && $result->manifestId === 'manifest-1' && $result->approvalRef === 'approval-1');
                self::ensure(DigestCalls::$hashCalls === 1 && DigestCalls::$hashedLength === 3);
                self::ensure(end(DigestCalls::$comparisons) === [self::ABC_SHA, self::ABC_SHA]);
            };
            $cases['TD01 operator explicit independent record and attestation reference'] = static function (): void {
                $result = self::verify(self::context(['trustMode' => 'OPERATOR_ATTESTED', 'selectionOrigin' => 'independent-review', 'selectionRef' => 'operator-attestation-17']), self::record(), 'abc');
                self::ensure($result->digestStatus === 'MATCH');
                self::ensure($result->trustMode === 'OPERATOR_ATTESTED' && $result->authenticity === 'OPERATOR_ATTESTED');
            };
            foreach (["a\0b", "line\n", "line\r\n", '', "\xff\0\x80"] as $index => $raw) {
                $cases['TD01 exact binary raw bytes '.$index] = static function () use ($raw): void {
                    $pin = \hash('sha256', $raw);
                    $result = self::verify(self::context(['manifestSha256' => $pin]), self::record(['manifestSha256' => $pin]), $raw, self::selection(['manifestSha256' => $pin]));
                    self::ensure($result->digestStatus === 'MATCH' && $result->actualDigest === $pin);
                    self::ensure(DigestCalls::$hashCalls === 1 && DigestCalls::$hashedLength === strlen($raw));
                };
            }
            foreach (['abd', 'abc'."\n", 'ABC', '{not-json'] as $index => $raw) {
                $cases['TD01 changed bytes and malformed manifest mismatch '.$index] = static function () use ($raw): void {
                    $result = self::verify(self::context(), self::record(), $raw);
                    self::failure($result, 'MANIFEST_UNTRUSTED', 'MISMATCH', 'G04');
                    self::ensure($result->digestStatus === 'MISMATCH' && $result->actualDigest === \hash('sha256', $raw));
                    self::ensure(DigestCalls::$hashCalls === 1 && DigestCalls::$parserCalls === 0);
                    self::ensure(end(DigestCalls::$comparisons) === [self::ABC_SHA, $result->actualDigest]);
                };
            }
            $cases['TD01 CRLF never normalizes to selected LF'] = static function (): void {
                $pin = \hash('sha256', "line\n");
                $result = self::verify(self::context(['manifestSha256' => $pin]), self::record(['manifestSha256' => $pin]), "line\r\n", self::selection(['manifestSha256' => $pin]));
                self::failure($result, 'MANIFEST_UNTRUSTED', 'MISMATCH', 'G04');
            };
            foreach ([null, [], true, 12, 'raw anchor JSON', new class {}] as $index => $bad) {
                $cases['TD01 decoded anchor root type '.$index] = static function () use ($bad): void {
                    self::failure(self::verify(self::context(), $bad, 'abc'), $bad === null ? 'ANCHOR_MISSING' : 'ANCHOR_INVALID');
                    self::ensure(DigestCalls::$hashCalls === 0);
                };
            }
            foreach (array_keys(get_object_vars(self::record())) as $field) {
                $cases['TD01 missing anchor '.$field] = static function () use ($field): void {
                    $candidate = self::record();
                    unset($candidate->{$field});
                    self::failure(self::verify(self::context(), $candidate, 'abc'), 'ANCHOR_INVALID');
                    self::ensure(DigestCalls::$hashCalls === 0);
                };
                foreach ([null, false, 1, [], (object) []] as $index => $wrong) {
                    $cases['TD01 anchor type '.$field.' '.$index] = static function () use ($field, $wrong): void {
                        self::failure(self::verify(self::context(), self::record([$field => $wrong]), 'abc'), 'ANCHOR_INVALID');
                        self::ensure(DigestCalls::$hashCalls === 0);
                    };
                }
            }
            foreach (['extra', 'C:\\private\\secret', "\0hidden"] as $index => $key) {
                $cases['TD01 unknown anchor field and safe diagnostic '.$index] = static function () use ($key): void {
                    $candidate = (object) array_replace(get_object_vars(self::record()), [$key => 'untrusted-secret']);
                    $result = self::verify(self::context(), $candidate, 'raw-secret');
                    self::failure($result, 'ANCHOR_INVALID');
                    $encoded = json_encode($result->toArray(), JSON_THROW_ON_ERROR);
                    self::ensure(! str_contains($encoded, 'secret') && ! str_contains($encoded, 'private') && ! str_contains($encoded, 'hidden'));
                    self::ensure($result->anchorId === null && $result->actualDigest === null && $result->authenticity === null);
                };
            }
            $cases['TD01 replaced required field preserves count but fails shape'] = static function (): void {
                $record = self::record();
                unset($record->approvalRef);
                $record->unexpected = 'x';
                self::failure(self::verify(self::context(), $record, 'abc'), 'ANCHOR_INVALID');
            };
            foreach (['2.0', '', '1.0'."\n", '1'] as $index => $version) {
                $cases['TD01 unsupported anchor version '.$index] = static fn () => self::failure(self::verify(self::context(), self::record(['anchorVersion' => $version]), 'abc'), 'ANCHOR_VERSION_UNSUPPORTED');
            }
            foreach (['active', 'UNKNOWN', '', 'ACTIVE'."\n"] as $index => $status) {
                $cases['TD01 invalid anchor status '.$index] = static fn () => self::failure(self::verify(self::context(), self::record(['status' => $status]), 'abc'), 'ANCHOR_INVALID');
            }
            $cases['TD01 revoked candidate before tuple or digest'] = static function (): void {
                self::failure(self::verify(self::context(), self::record(['status' => 'REVOKED']), 'bad'), 'ANCHOR_REVOKED');
                self::ensure(DigestCalls::$hashCalls === 0);
            };
            foreach (['anchorId', 'manifestId', 'profileId', 'approvalRef', 'verifierReleaseId'] as $field) {
                foreach (['', str_repeat('x', 81), 'bad value', '../bad', "id\n", "\xc3\xa9", "\0"] as $index => $bad) {
                    $cases['TD01 invalid ID '.$field.' '.$index] = static fn () => self::failure(self::verify(self::context(), self::record([$field => $bad]), 'abc'), 'ANCHOR_INVALID');
                }
                foreach (['x', str_repeat('A', 80), 'A-z_0.9'] as $index => $id) {
                    $cases['TD01 valid ID boundaries '.$field.' '.$index] = static function () use ($field, $id): void {
                        $result = self::verify(self::context([$field => $id]), self::record([$field => $id]), 'abc', self::selection([$field => $id]));
                        self::ensure($result->digestStatus === 'MATCH');
                    };
                }
            }
            foreach (['manifestSha256', 'profileSha256'] as $field) {
                foreach ([str_repeat('A', 64), str_repeat('a', 63), str_repeat('a', 65), str_repeat('g', 64), str_repeat('a', 64)."\n", '', '0e'.str_repeat('1', 62)] as $index => $bad) {
                    // A syntactically valid 0e digest is a binding mismatch, never numeric equality.
                    $code = $index === 6 ? ($field === 'profileSha256' ? 'PROFILE_MISMATCH' : 'ANCHOR_SELECTION_MISMATCH') : 'ANCHOR_INVALID';
                    $cases['TD01 exact digest grammar '.$field.' '.$index] = static fn () => self::failure(self::verify(self::context(), self::record([$field => $bad]), 'abc'), $code);
                }
            }
            foreach (['anchorId', 'manifestId', 'manifestSha256', 'approvalRef', 'profileId', 'profileSha256', 'verifierReleaseId'] as $field) {
                $value = str_ends_with($field, 'Sha256') ? str_repeat('e', 64) : 'substituted';
                $code = in_array($field, ['profileId', 'profileSha256'], true) ? 'PROFILE_MISMATCH'
                    : ($field === 'verifierReleaseId' ? 'VERIFIER_RELEASE_MISMATCH' : 'ANCHOR_SELECTION_MISMATCH');
                $cases['TD01 candidate substitution '.$field] = static function () use ($field, $value, $code): void {
                    self::failure(self::verify(self::context(), self::record([$field => $value]), 'abc'), $code);
                    self::ensure(DigestCalls::$hashCalls === 0);
                };
                $cases['TD01 selected record and candidate cannot replace context '.$field] = static function () use ($field, $value, $code): void {
                    self::failure(self::verify(self::context(), self::record([$field => $value]), 'abc', self::selection([$field => $value])), $code);
                    self::ensure(DigestCalls::$hashCalls === 0);
                };
            }
            $cases['TD01 candidate manifest and digest replacement cannot select new pin'] = static function (): void {
                $pin = \hash('sha256', 'replacement');
                self::failure(self::verify(self::context(), self::record(['manifestSha256' => $pin]), 'replacement'), 'ANCHOR_SELECTION_MISMATCH');
                self::ensure(DigestCalls::$hashCalls === 0);
            };
            $cases['TD01 missing provider selection has no candidate fallback'] = static function (): void {
                self::failure(self::verify(self::context(), self::record(), 'abc', new AnchorProvider(null)), 'ANCHOR_MISSING');
                self::ensure(DigestCalls::$hashCalls === 0);
            };
            $cases['TD01 selected record syntax is also validated'] = static fn () => self::failure(self::verify(self::context(), self::record(), 'abc', self::selection(['anchorId' => '../unsafe'])), 'ANCHOR_INVALID');
            $cases['TD01 selected revoked status is denied'] = static fn () => self::failure(self::verify(self::context(), self::record(), 'abc', self::selection(['status' => 'REVOKED'])), 'ANCHOR_REVOKED');
            foreach ([null, [], new stdClass] as $index => $bad) {
                $cases['TD01 missing explicit context '.$index] = static fn () => self::failure(self::verify($bad, self::record(), 'abc'), 'CONFIG_INVALID', 'DENIED', 'G01');
            }
            foreach (['DEPLOYMENT', 'PROTECTED_DEPLOYMENT', 'EXTERNAL_ANCHOR'] as $mode) {
                $cases['TD01 unsupported protected trust '.$mode] = static function () use ($mode): void {
                    $result = self::verify(self::context(['trustMode' => $mode, 'selectionRef' => '']), null, 'abc', new AnchorProvider(null));
                    self::failure($result, 'CAPABILITY_UNAVAILABLE', 'INCONCLUSIVE', 'G01');
                    self::ensure($result->trustMode === null && $result->authenticity === null && DigestCalls::$hashCalls === 0);
                };
            }
            foreach (['', 'fixture', 'UNKNOWN', 'FIXTURE'."\n"] as $index => $mode) {
                $cases['TD01 no default or implicit mode '.$index] = static fn () => self::failure(self::verify(self::context(['trustMode' => $mode]), self::record(), 'abc'), 'CONFIG_INVALID', 'DENIED', 'G01');
            }
            foreach (['selectionOrigin', 'selectionRef'] as $field) {
                foreach (['', ' ', "\t", " \r\n "] as $index => $bad) {
                    foreach (['FIXTURE', 'OPERATOR_ATTESTED'] as $mode) {
                        $cases['TD01 required independent reference '.$mode.' '.$field.' '.$index] = static fn () => self::failure(self::verify(self::context(['trustMode' => $mode, $field => $bad]), self::record(), 'abc'), 'CONFIG_INVALID', 'DENIED', 'G01');
                    }
                }
            }
            $cases['TD01 independent references remain opaque caller values'] = static function (): void {
                foreach (['approved record #17', 'független-jóváhagyás', str_repeat('r', 300)] as $ref) {
                    $result = self::verify(self::context(['trustMode' => 'OPERATOR_ATTESTED', 'selectionOrigin' => $ref, 'selectionRef' => $ref]), self::record(), 'abc');
                    self::ensure($result->digestStatus === 'MATCH');
                    self::ensure(! str_contains(json_encode($result->toArray(), JSON_THROW_ON_ERROR), $ref));
                }
            };
            foreach (['anchorVersion' => '2.0', 'anchorId' => '', 'manifestSha256' => 'bad', 'profileSha256' => 'BAD', 'status' => 'REVOKED'] as $field => $bad) {
                $cases['TD01 context expected tuple validated '.$field] = static fn () => self::failure(self::verify(self::context([$field => $bad]), self::record(), 'abc'), 'CONFIG_INVALID', 'DENIED', 'G01');
            }
            foreach ([-1, 0, 1] as $delta) {
                $cases['TD01 raw 16MiB boundary '.$delta] = static function () use ($delta): void {
                    $raw = str_repeat('x', TrustedDigestVerifier::MAX_RAW_BYTES + $delta);
                    $pin = \hash('sha256', $raw);
                    $result = self::verify(self::context(['manifestSha256' => $pin]), self::record(['manifestSha256' => $pin]), $raw, self::selection(['manifestSha256' => $pin]));
                    if ($delta > 0) {
                        self::failure($result, 'MANIFEST_SIZE_EXCEEDED', 'DENIED', 'G03');
                        self::ensure(DigestCalls::$hashCalls === 0 && $result->actualDigest === null);
                    } else {
                        self::ensure($result->digestStatus === 'MATCH' && DigestCalls::$hashCalls === 1);
                        self::ensure(DigestCalls::$hashedLength === strlen($raw));
                    }
                };
            }
            $cases['TD01 deterministic failure precedence including oversize'] = static function (): void {
                $raw = str_repeat('x', TrustedDigestVerifier::MAX_RAW_BYTES + 1);
                self::failure(self::verify(self::context(['selectionRef' => '']), null, $raw), 'CONFIG_INVALID', 'DENIED', 'G01');
                self::failure(self::verify(self::context(), null, $raw, new AnchorProvider(null)), 'ANCHOR_MISSING');
                self::failure(self::verify(self::context(), self::record(['anchorVersion' => '2.0', 'status' => 'REVOKED']), $raw), 'ANCHOR_VERSION_UNSUPPORTED');
                self::failure(self::verify(self::context(), self::record(['status' => 'REVOKED', 'profileId' => 'other']), $raw), 'ANCHOR_REVOKED');
                self::failure(self::verify(self::context(), self::record(['anchorId' => 'other', 'profileId' => 'other', 'verifierReleaseId' => 'other']), $raw), 'ANCHOR_SELECTION_MISMATCH');
                self::failure(self::verify(self::context(), self::record(['profileId' => 'other', 'verifierReleaseId' => 'other']), $raw), 'PROFILE_MISMATCH');
                self::failure(self::verify(self::context(), self::record(['verifierReleaseId' => 'other']), $raw), 'VERIFIER_RELEASE_MISMATCH');
                self::failure(self::verify(self::context(), self::record(), $raw), 'MANIFEST_SIZE_EXCEEDED', 'DENIED', 'G03');
                self::ensure(DigestCalls::$hashCalls === 0);
            };
            $cases['TD01 property order does not change validation or result'] = static function (): void {
                $first = self::verify(self::context(), self::record(), 'abc')->toArray();
                $reordered = (object) array_reverse(get_object_vars(self::record()), true);
                self::ensure(self::verify(self::context(), $reordered, 'abc')->toArray() === $first);
            };
            $cases['TD01 immutable context selection inputs and result'] = static function (): void {
                $context = self::context();
                $candidate = self::record();
                $provider = self::selection();
                $before = serialize([$context, $candidate, $provider]);
                $result = self::verify($context, $candidate, 'abc', $provider);
                self::ensure(serialize([$context, $candidate, $provider]) === $before);
                $candidate->anchorId = 'attacker-change';
                self::ensure($result->anchorId === 'anchor-1' && $provider->selectedAnchor()->anchorId === 'anchor-1');
                foreach ([$context, $provider->selectedAnchor(), $result] as $value) {
                    self::ensure((new ReflectionClass($value))->isReadOnly() && (new ReflectionClass($value))->isFinal());
                }
                foreach (['scope' => 'FULL', 'fileInventoryStatus' => 'VERIFIED', 'filesystemIdentityStatus' => 'VERIFIED', 'executionAuthorization' => 'AUTHORIZED', 'canLaunchPoC' => true, 'digestStatus' => 'VERIFIED', 'errors' => ['forged']] as $property => $value) {
                    $caught = false;
                    try {
                        $result->{$property} = $value;
                    } catch (Error) {
                        $caught = true;
                    }
                    self::ensure($caught);
                }
                $copy = $result->toArray();
                $copy['canLaunchPoC'] = true;
                self::ensure(! $result->canLaunchPoC);
            };
            $cases['TD01 verifier reuse has no stale phase state'] = static function (): void {
                $verifier = new TrustedDigestVerifier(self::selection());
                foreach (['abc', 'bad', 'abc'] as $raw) {
                    DigestCalls::reset();
                    $result = $verifier->verify(self::context(), self::record(), $raw);
                    self::invariants($result);
                    self::ensure($result->digestStatus === ($raw === 'abc' ? 'MATCH' : 'MISMATCH'));
                }
            };
            $cases['TD01 unexpected programming exception propagates'] = static function (): void {
                DigestCalls::reset();
                DigestCalls::$throwOnHash = true;
                $caught = false;
                try {
                    (new TrustedDigestVerifier(self::selection()))->verify(self::context(), self::record(), 'abc');
                } catch (LogicException $error) {
                    $caught = $error->getMessage() === 'Synthetic hash programming defect.';
                } finally {
                    DigestCalls::$throwOnHash = false;
                }
                self::ensure($caught && DigestCalls::$hashCalls === 1);
            };
            $cases['TD01 raw anchor is rejected without duplicate-key proof claim'] = static function (): void {
                self::failure(self::verify(self::context(), '{"anchorId":"a","anchorId":"b"}', 'abc'), 'ANCHOR_INVALID');
                self::ensure(DigestCalls::$parserCalls === 0);
            };
            $cases['TD01 no filesystem or autoload activity on match mismatch and deny'] = static function (): void {
                $context = self::context();
                $candidate = self::record();
                $verifier = new TrustedDigestVerifier(self::selection());
                $loads = 0;
                $loader = static function (string $name) use (&$loads): void {
                    $loads++;
                };
                $included = get_included_files();
                Td01NoFilesystem::$calls = 0;
                self::ensure(stream_wrapper_unregister('file'));
                self::ensure(stream_wrapper_register('file', Td01NoFilesystem::class));
                spl_autoload_register($loader);
                try {
                    foreach (['abc', 'mismatch'] as $raw) {
                        self::invariants($verifier->verify($context, $candidate, $raw));
                    }
                    self::invariants($verifier->verify($context, null, 'abc'));
                } finally {
                    spl_autoload_unregister($loader);
                    stream_wrapper_restore('file');
                }
                self::ensure(Td01NoFilesystem::$calls === 0 && $loads === 0);
                self::ensure(get_included_files() === $included);
            };
            $cases['TD01 static dependency boundary has no parser schema I/O or imports'] = static function (): void {
                $forbidden = ['json_decode', 'ManifestValidator', 'CanonicalJson', 'StrictJsonTokenizer', 'ManifestVerificationResult', 'file_get_contents', 'fopen', 'file_put_contents', 'hash_file', 'getenv', 'getcwd', 'config', 'curl_exec', 'fsockopen', 'stream_socket_client', 'exec', 'system', 'shell_exec', 'proc_open', 'PDO', 'mysqli_connect'];
                foreach (['TrustAnchor', 'TrustContext', 'AnchorProvider', 'AnchorValidator', 'TrustedDigestVerifier', 'DigestComparisonResult'] as $name) {
                    $source = file_get_contents(dirname(__DIR__).'/src/'.$name.'.php');
                    foreach (token_get_all($source) as $token) {
                        if (! is_array($token)) {
                            continue;
                        }
                        self::ensure(! in_array($token[0], [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE, T_EVAL], true));
                        if ($token[0] === T_STRING) {
                            self::ensure(! in_array($token[1], $forbidden, true));
                        }
                        if (in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                            self::ensure(! str_contains($token[1], 'Illuminate') && ! str_contains($token[1], 'Prototype'));
                        }
                    }
                }
            };

            $cases['R01-T01 former boolean factory is not callable'] = static function (): void {
                self::ensure(! is_callable([DigestComparisonResult::class, 'comparison']));
                self::rejected(static fn () => DigestComparisonResult::comparison(true, self::selection()->selectedAnchor(), 'OPERATOR_ATTESTED', str_repeat('b', 64)), Error::class);
            };
            foreach ([false, true] as $factory) {
                $path = $factory ? 'factory' : 'verify';
                $cases['R01-T02 no forged operator authenticity '.$path] = static function () use ($factory): void {
                    foreach ([null, self::context(['trustMode' => 'OPERATOR_ATTESTED', 'selectionRef' => '']), self::context(['trustMode' => 'OPERATOR_ATTESTED', 'selectionOrigin' => ''])] as $context) {
                        $result = self::r01Verify($factory, $context, self::record(), 'abc');
                        self::failure($result, 'CONFIG_INVALID', 'DENIED', 'G01');
                        self::ensure($result->authenticity === null && DigestCalls::$hashCalls === 0);
                    }
                };
                $cases['R01-T03 ACTIVE candidate alone establishes no trust '.$path] = static function () use ($factory): void {
                    self::failure(self::r01Verify($factory, null, self::record(), 'abc'), 'CONFIG_INVALID', 'DENIED', 'G01');
                    self::failure(self::r01Verify($factory, self::context(), self::record(), 'abc', new AnchorProvider(null)), 'ANCHOR_MISSING');
                    self::ensure(DigestCalls::$hashCalls === 0);
                };
                $cases['R01-T04 legitimate fixture success '.$path] = static function () use ($factory): void {
                    $result = self::r01Verify($factory, self::context(), self::record(), 'abc');
                    self::ensure($result->digestStatus === 'MATCH' && $result->authenticity === 'SIMULATED');
                    self::ensure($result->actualDigest === self::ABC_SHA && $result->expectedDigest === self::ABC_SHA);
                    self::ensure(DigestCalls::$hashCalls === 1 && DigestCalls::$digestComparisons === 1);
                };
                $cases['R01-T05 legitimate operator success '.$path] = static function () use ($factory): void {
                    $result = self::r01Verify($factory, self::context(['trustMode' => 'OPERATOR_ATTESTED', 'selectionOrigin' => 'independent-owner', 'selectionRef' => 'attestation-r01']), self::record(), 'abc');
                    self::ensure($result->digestStatus === 'MATCH' && $result->authenticity === 'OPERATOR_ATTESTED');
                    self::ensure(DigestCalls::$hashCalls === 1 && DigestCalls::$digestComparisons === 1);
                };
                $cases['R01-T06 negative gates and precedence '.$path] = static function () use ($factory): void {
                    foreach ([
                        [['anchorId' => 'other', 'profileId' => 'other', 'verifierReleaseId' => 'other'], 'ANCHOR_SELECTION_MISMATCH'],
                        [['profileId' => 'other', 'verifierReleaseId' => 'other'], 'PROFILE_MISMATCH'],
                        [['profileSha256' => str_repeat('e', 64)], 'PROFILE_MISMATCH'],
                        [['verifierReleaseId' => 'other'], 'VERIFIER_RELEASE_MISMATCH'],
                        [['status' => 'REVOKED'], 'ANCHOR_REVOKED'],
                    ] as [$changes, $code]) {
                        self::failure(self::r01Verify($factory, self::context(), self::record($changes), 'bad'), $code);
                        self::ensure(DigestCalls::$hashCalls === 0);
                    }
                    self::failure(self::r01Verify($factory, self::context(['trustMode' => 'DEPLOYMENT']), self::record(), 'abc'), 'CAPABILITY_UNAVAILABLE', 'INCONCLUSIVE', 'G01');
                    self::failure(self::r01Verify($factory, self::context(), self::record(), 'bad'), 'MANIFEST_UNTRUSTED', 'MISMATCH', 'G04');
                    self::ensure(DigestCalls::$hashCalls === 1 && DigestCalls::$digestComparisons === 1);
                    $oversize = str_repeat('x', TrustedDigestVerifier::MAX_RAW_BYTES + 1);
                    self::failure(self::r01Verify($factory, self::context(), self::record(['anchorId' => 'other']), $oversize), 'ANCHOR_SELECTION_MISMATCH');
                    self::failure(self::r01Verify($factory, self::context(), self::record(), $oversize), 'MANIFEST_SIZE_EXCEEDED', 'DENIED', 'G03');
                    self::ensure(DigestCalls::$hashCalls === 0 && DigestCalls::$digestComparisons === 0);
                };
                $cases['R01-T09 sequential use recomputes every attempt '.$path] = static function () use ($factory): void {
                    $verifier = new TrustedDigestVerifier(self::selection());
                    $oversize = str_repeat('x', TrustedDigestVerifier::MAX_RAW_BYTES + 1);
                    foreach ([
                        [self::context(), 'abc', 'MATCH', null, 1],
                        [self::context(), 'bad', 'MISMATCH', 'MANIFEST_UNTRUSTED', 1],
                        [null, 'abc', 'NOT_EVALUATED', 'CONFIG_INVALID', 0],
                        [self::context(), $oversize, 'NOT_EVALUATED', 'MANIFEST_SIZE_EXCEEDED', 0],
                        [self::context(), 'abc', 'MATCH', null, 1],
                    ] as [$context, $raw, $status, $code, $hashes]) {
                        DigestCalls::reset();
                        $result = $factory ? DigestComparisonResult::fromVerification($verifier, $context, self::record(), $raw) : $verifier->verify($context, self::record(), $raw);
                        self::invariants($result);
                        self::ensure($result->digestStatus === $status && $result->primaryFailureCode === $code);
                        self::ensure(DigestCalls::$hashCalls === $hashes && DigestCalls::$digestComparisons === $hashes);
                        self::ensure($result->anchorId === ($hashes === 1 ? 'anchor-1' : null));
                        self::ensure($result->actualDigest === ($hashes === 1 ? \hash('sha256', $raw) : null));
                    }
                };
            }
            $cases['R01-T07 all result categories are immutable and scoped'] = static function (): void {
                $results = [self::verify(self::context(), self::record(), 'abc'), self::verify(self::context(), self::record(), 'bad')];
                foreach (['CONFIG_INVALID', 'CAPABILITY_UNAVAILABLE', 'ANCHOR_MISSING', 'ANCHOR_INVALID', 'ANCHOR_VERSION_UNSUPPORTED', 'ANCHOR_REVOKED', 'ANCHOR_SELECTION_MISMATCH', 'PROFILE_MISMATCH', 'VERIFIER_RELEASE_MISMATCH', 'MANIFEST_SIZE_EXCEEDED'] as $code) {
                    $result = DigestComparisonResult::failure($code);
                    self::invariants($result);
                    self::ensure($result->digestStatus === 'NOT_EVALUATED' && $result->authenticity === null);
                    $results[] = $result;
                }
                foreach ($results as $result) {
                    $before = $result->toArray();
                    foreach (['phaseVerdict' => 'MATCH', 'digestStatus' => 'MATCH', 'authenticity' => 'OPERATOR_ATTESTED', 'primaryFailureCode' => null, 'trustMode' => 'OPERATOR_ATTESTED', 'anchorId' => 'forged', 'actualDigest' => str_repeat('b', 64), 'scope' => 'FULL', 'canLaunchPoC' => true, 'errors' => []] as $field => $value) {
                        self::rejected(static function () use ($result, $field, $value): void {
                            $result->{$field} = $value;
                        }, Error::class);
                    }
                    self::ensure($result->toArray() === $before);
                    self::invariants($result);
                }
                foreach (['MATCH', 'VERIFIED', 'MANIFEST_UNTRUSTED', 'OPERATOR_ATTESTED', ''] as $code) {
                    self::rejected(static fn () => DigestComparisonResult::failure($code), \InvalidArgumentException::class);
                }
            };
            $cases['R01-T08 actual public APIs reject construction and injected facts'] = static function (): void {
                self::rejected(static fn () => new DigestComparisonResult, Error::class);
                $forged = ['matched' => true, 'mode' => 'OPERATOR_ATTESTED', 'selected' => self::selection()->selectedAnchor(), 'actualDigest' => str_repeat('b', 64)];
                self::rejected(static fn () => DigestComparisonResult::assembleComparison($forged), Error::class);
                $fake = new class
                {
                    public int $calls = 0;

                    public function evaluate(mixed ...$inputs): array
                    {
                        $this->calls++;

                        return ['matched' => true];
                    }
                };
                foreach ([$fake, $forged, true, static fn () => $forged] as $untrusted) {
                    self::rejected(static fn () => DigestComparisonResult::fromVerification($untrusted, self::context(), self::record(), 'abc'), \TypeError::class);
                }
                self::ensure($fake->calls === 0);
                $verifier = new TrustedDigestVerifier(self::selection());
                DigestCalls::reset();
                $facts = $verifier->evaluate(self::context(), self::record(), 'abc');
                self::ensure(is_array($facts) && $facts['matched'] && DigestCalls::$hashCalls === 1 && DigestCalls::$digestComparisons === 1);
                $facts['mode'] = 'OPERATOR_ATTESTED';
                $facts['actualDigest'] = str_repeat('b', 64);
                self::rejected(static fn () => DigestComparisonResult::fromVerification($facts, null, self::record(), 'abc'), \TypeError::class);
                self::rejected(static fn () => DigestComparisonResult::assembleComparison($facts), Error::class);
                self::failure(DigestComparisonResult::fromVerification($verifier, null, self::record(), 'abc'), 'CONFIG_INVALID', 'DENIED', 'G01');
                $methods = array_map(static fn ($method) => $method->getName(), (new ReflectionClass(DigestComparisonResult::class))->getMethods(\ReflectionMethod::IS_PUBLIC));
                sort($methods);
                self::ensure($methods === ['__unserialize', 'failure', 'fromVerification', 'toArray']);
                self::ensure((new ReflectionClass(DigestComparisonResult::class))->getConstructor()->isPrivate());
                self::ensure((new ReflectionClass(TrustedDigestVerifier::class))->isFinal());
            };
            $cases['R01-T08 native hydration rejects real and forged serialized states'] = static function (): void {
                foreach (['abc', 'bad'] as $raw) {
                    $result = self::verify(self::context(), self::record(), $raw);
                    $payload = serialize($result);
                    self::rejected(static fn () => unserialize($payload, ['allowed_classes' => [DigestComparisonResult::class]]), \InvalidArgumentException::class);
                    $before = $result->toArray();
                    self::rejected(static fn () => $result->__unserialize(['digestStatus' => 'MATCH']), \InvalidArgumentException::class);
                    self::ensure($result->toArray() === $before);
                }
                $serialized = serialize((object) ['digestStatus' => 'MATCH', 'phaseVerdict' => 'MATCH', 'authenticity' => 'OPERATOR_ATTESTED', 'scope' => 'FULL', 'canLaunchPoC' => true, 'expectedDigest' => str_repeat('a', 64), 'actualDigest' => str_repeat('b', 64)]);
                $prefix = 'O:8:"stdClass"';
                self::ensure(str_starts_with($serialized, $prefix));
                $forged = 'O:'.strlen(DigestComparisonResult::class).':"'.DigestComparisonResult::class.'"'.substr($serialized, strlen($prefix));
                self::rejected(static fn () => unserialize($forged, ['allowed_classes' => [DigestComparisonResult::class]]), \InvalidArgumentException::class);
            };
            // The installed optional monitor observes the unchanged final provider without production hooks.
            // Run this case with -n -d zend_extension=<installed xdebug DLL> -d xdebug.mode=develop.
            if (function_exists('xdebug_start_function_monitor') && in_array('develop', xdebug_info('mode'), true)) {
                $cases['R01 invocation monitor observes one selection and no recursive evaluation'] = static function (): void {
                    $verifier = new TrustedDigestVerifier(self::selection());
                    $oversize = str_repeat('x', TrustedDigestVerifier::MAX_RAW_BYTES + 1);
                    foreach ([false, true] as $factory) {
                        foreach ([
                            [self::context(), self::record(), 'abc', 1, 1],
                            [self::context(), self::record(), 'bad', 1, 1],
                            [null, self::record(), 'abc', 0, 0],
                            [self::context(), null, 'abc', 1, 0],
                            [self::context(), self::record(['anchorId' => 'other']), 'abc', 1, 0],
                            [self::context(), self::record(), $oversize, 1, 0],
                        ] as [$context, $candidate, $raw, $selections, $hashes]) {
                            DigestCalls::reset();
                            xdebug_start_function_monitor([
                                AnchorProvider::class.'->selectedAnchor',
                                TrustedDigestVerifier::class.'->verify',
                                TrustedDigestVerifier::class.'->evaluate',
                                DigestComparisonResult::class.'::fromVerification',
                            ]);
                            $eventStart = count(xdebug_get_monitored_functions());
                            try {
                                $result = $factory ? DigestComparisonResult::fromVerification($verifier, $context, $candidate, $raw) : $verifier->verify($context, $candidate, $raw);
                                $events = array_slice(xdebug_get_monitored_functions(), $eventStart);
                            } finally {
                                xdebug_stop_function_monitor();
                            }
                            $counts = [];
                            foreach ($events as $event) {
                                $name = str_replace('->', '::', ltrim($event['function'], '\\'));
                                $counts[$name] = ($counts[$name] ?? 0) + 1;
                            }
                            self::ensure(($counts[AnchorProvider::class.'::selectedAnchor'] ?? 0) === $selections);
                            self::ensure(($counts[TrustedDigestVerifier::class.'::evaluate'] ?? 0) === 1);
                            self::ensure(($counts[DigestComparisonResult::class.'::fromVerification'] ?? 0) === 1);
                            self::ensure(($counts[TrustedDigestVerifier::class.'::verify'] ?? 0) === ($factory ? 0 : 1));
                            self::ensure(DigestCalls::$hashCalls === $hashes && DigestCalls::$digestComparisons === $hashes);
                            self::invariants($result);
                        }
                    }
                    echo 'R01 monitor: 12 attempts; provider selection 0/1; evaluation/factory once; SHA/G04 comparison 0/1; no recursion.'.PHP_EOL;
                };
            }

            return $cases;
        }

        private static function r01Verify(bool $factory, mixed $context, mixed $candidate, string $raw, ?AnchorProvider $provider = null): DigestComparisonResult
        {
            DigestCalls::reset();
            $verifier = new TrustedDigestVerifier($provider ?? self::selection());
            $result = $factory ? DigestComparisonResult::fromVerification($verifier, $context, $candidate, $raw) : $verifier->verify($context, $candidate, $raw);
            self::invariants($result);
            self::ensure(DigestCalls::$hashCalls <= 1 && DigestCalls::$digestComparisons === DigestCalls::$hashCalls);
            self::ensure(DigestCalls::$parserCalls === 0);

            return $result;
        }

        /** @param class-string<\Throwable> $exceptionClass */
        private static function rejected(Closure $attempt, string $exceptionClass): void
        {
            $caught = false;
            try {
                $attempt();
            } catch (\Throwable $error) {
                $caught = $error instanceof $exceptionClass;
            }
            self::ensure($caught);
        }

        private static function record(array $changes = []): stdClass
        {
            return (object) array_replace([
                'anchorVersion' => '1.0', 'anchorId' => 'anchor-1', 'manifestId' => 'manifest-1',
                'manifestSha256' => self::ABC_SHA, 'profileId' => 'profile-1', 'profileSha256' => str_repeat('d', 64),
                'approvalRef' => 'approval-1', 'verifierReleaseId' => 'release-1', 'status' => 'ACTIVE',
            ], $changes);
        }

        private static function context(array $changes = []): TrustContext
        {
            $fields = array_replace(['trustMode' => 'FIXTURE', 'selectionOrigin' => 'independent-fixture-harness', 'selectionRef' => 'synthetic-record-1'] + get_object_vars(self::record()), $changes);

            return new TrustContext(...$fields);
        }

        private static function selection(array $changes = []): AnchorProvider
        {
            return new AnchorProvider(new TrustAnchor(...get_object_vars(self::record($changes))));
        }

        private static function verify(mixed $context, mixed $candidate, string $raw, ?AnchorProvider $provider = null): DigestComparisonResult
        {
            DigestCalls::reset();
            $result = (new TrustedDigestVerifier($provider ?? self::selection()))->verify($context, $candidate, $raw);
            self::invariants($result);
            self::ensure(DigestCalls::$parserCalls === 0);

            return $result;
        }

        private static function failure(DigestComparisonResult $result, string $code, string $phase = 'DENIED', string $stage = 'G02'): void
        {
            self::ensure($result->phaseVerdict === $phase && $result->primaryFailureCode === $code);
            self::ensure(count($result->errors) === 1 && $result->errors[0]['code'] === $code && $result->errors[0]['stage'] === $stage);
            self::ensure($result->errors[0]['path'] === '$' && strlen($result->errors[0]['message']) < 160);
            if ($code !== 'MANIFEST_UNTRUSTED') {
                self::ensure($result->digestStatus === 'NOT_EVALUATED' && $result->actualDigest === null && $result->expectedDigest === null);
                self::ensure($result->anchorId === null && $result->manifestId === null && $result->approvalRef === null && $result->authenticity === null);
            }
        }

        private static function invariants(DigestComparisonResult $result): void
        {
            self::$resultsChecked++;
            $data = $result->toArray();
            foreach (['scope' => 'MANIFEST_ONLY', 'fileInventoryStatus' => 'NOT_EVALUATED', 'filesystemIdentityStatus' => 'NOT_EVALUATED', 'executionAuthorization' => 'NOT_EVALUATED', 'canLaunchPoC' => false] as $key => $value) {
                self::ensure($result->{$key} === $value && $data[$key] === $value);
            }
            foreach (['schemaStatus', 'canonicalStatus', 'bindingStatus', 'anchorHistoryStatus'] as $key) {
                self::ensure($result->{$key} === 'NOT_EVALUATED');
            }
            self::ensure(! array_key_exists('manifestVerdict', $data) && ! array_key_exists('manifest', $data));
            self::ensure(count($result->errors) <= 1 && ! $result->diagnosticsTruncated);
        }

        private static function ensure(bool $condition): void
        {
            self::$assertions++;
            if (! $condition) {
                throw new RuntimeException('TD01 assertion '.self::$assertions.' failed.');
            }
        }
    }

    final class Td01NoFilesystem
    {
        public mixed $context;

        public static int $calls = 0;

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
    }
}
