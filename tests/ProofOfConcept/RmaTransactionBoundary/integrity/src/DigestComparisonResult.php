<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use InvalidArgumentException;

/** Digest phase only: MATCH never supplies a manifest verdict or execution authority. */
final readonly class DigestComparisonResult
{
    private const FAILURES = [
        'CONFIG_INVALID' => ['G01', 'Invalid explicit trust context.'],
        'CAPABILITY_UNAVAILABLE' => ['G01', 'Requested trust mode or runtime capability is unavailable.'],
        'ANCHOR_MISSING' => ['G02', 'An independently selected anchor and candidate are required.'],
        'ANCHOR_INVALID' => ['G02', 'Anchor does not satisfy the exact decoded record contract.'],
        'ANCHOR_VERSION_UNSUPPORTED' => ['G02', 'Anchor version is unsupported.'],
        'ANCHOR_REVOKED' => ['G02', 'Anchor is revoked.'],
        'ANCHOR_SELECTION_MISMATCH' => ['G02', 'Anchor does not match the independently selected record.'],
        'PROFILE_MISMATCH' => ['G02', 'Anchor profile does not match the independent selection.'],
        'VERIFIER_RELEASE_MISMATCH' => ['G02', 'Verifier release does not match the independent selection.'],
        'MANIFEST_SIZE_EXCEEDED' => ['G03', 'Original raw manifest exceeds 16777216 bytes.'],
        'MANIFEST_UNTRUSTED' => ['G04', 'Original raw manifest digest does not match the selected digest.'],
    ];

    public string $scope;

    public string $fileInventoryStatus;

    public string $filesystemIdentityStatus;

    public string $executionAuthorization;

    public bool $canLaunchPoC;

    public string $schemaStatus;

    public string $canonicalStatus;

    public string $bindingStatus;

    public string $anchorHistoryStatus;

    public bool $diagnosticsTruncated;

    /** @param list<array{stage: string, path: string, code: string, message: string}> $errors */
    private function __construct(
        public string $phaseVerdict,
        public string $digestStatus,
        public ?string $primaryFailureCode,
        public ?string $trustMode,
        public ?string $authenticity,
        public ?string $anchorId,
        public ?string $manifestId,
        public ?string $approvalRef,
        public ?string $expectedDigest,
        public ?string $actualDigest,
        public array $errors,
    ) {
        $this->scope = 'MANIFEST_ONLY';
        $this->fileInventoryStatus = 'NOT_EVALUATED';
        $this->filesystemIdentityStatus = 'NOT_EVALUATED';
        $this->executionAuthorization = 'NOT_EVALUATED';
        $this->canLaunchPoC = false;
        $this->schemaStatus = 'NOT_EVALUATED';
        $this->canonicalStatus = 'NOT_EVALUATED';
        // Decoded manifest binding and global record history are later phases.
        $this->bindingStatus = 'NOT_EVALUATED';
        $this->anchorHistoryStatus = 'NOT_EVALUATED';
        $this->diagnosticsTruncated = false;
    }

    public static function failure(string $code): self
    {
        if (! isset(self::FAILURES[$code]) || $code === 'MANIFEST_UNTRUSTED') {
            throw new InvalidArgumentException('Unsupported digest-phase failure.');
        }

        return new self(
            $code === 'CAPABILITY_UNAVAILABLE' ? 'INCONCLUSIVE' : 'DENIED',
            'NOT_EVALUATED', $code, null, null, null, null, null, null, null,
            self::diagnostic($code),
        );
    }

    /** No caller-supplied outcome is accepted; the concrete final verifier runs all gates. */
    public static function fromVerification(
        TrustedDigestVerifier $verifier,
        mixed $context,
        mixed $candidateAnchor,
        string $rawBytes,
    ): self {
        $evaluation = $verifier->evaluate($context, $candidateAnchor, $rawBytes);
        if ($evaluation instanceof self) {
            return $evaluation;
        }

        return self::assembleComparison($evaluation);
    }

    /** @param array{matched: bool, selected: TrustAnchor, mode: string, actualDigest: string} $evaluation */
    private static function assembleComparison(array $evaluation): self
    {
        $matched = $evaluation['matched'];
        $selected = $evaluation['selected'];
        $mode = $evaluation['mode'];
        $actualDigest = $evaluation['actualDigest'];
        $code = $matched ? null : 'MANIFEST_UNTRUSTED';

        return new self(
            $matched ? 'MATCH' : 'MISMATCH', $matched ? 'MATCH' : 'MISMATCH', $code,
            $mode, $mode === 'FIXTURE' ? 'SIMULATED' : 'OPERATOR_ATTESTED',
            $selected->anchorId, $selected->manifestId, $selected->approvalRef,
            $selected->manifestSha256, $actualDigest, $code === null ? [] : self::diagnostic($code),
        );
    }

    /** Native object hydration must never create verification evidence. */
    public function __unserialize(array $data): void
    {
        throw new InvalidArgumentException('Digest verification results cannot be unserialized.');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @return list<array{stage: string, path: string, code: string, message: string}> */
    private static function diagnostic(string $code): array
    {
        [$stage, $message] = self::FAILURES[$code];

        return [['stage' => $stage, 'path' => '$', 'code' => $code, 'message' => $message]];
    }
}
