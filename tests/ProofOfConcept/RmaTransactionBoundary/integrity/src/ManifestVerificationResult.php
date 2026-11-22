<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use InvalidArgumentException;

/** Manifest-only evidence. Neither verdict spelling authorizes execution. */
final readonly class ManifestVerificationResult
{
    public string $resultVersion;

    public string $scope;

    /** Exact alias of the architecture's manifestVerdict, not a full input verdict. */
    public string $verdict;

    public string $anchorHistoryStatus;

    public string $fileInventoryStatus;

    public string $filesystemIdentityStatus;

    public string $executionAuthorization;

    public bool $canLaunchPoC;

    /** @param list<array{stage: string, path: string, code: string, message: string}> $errors */
    private function __construct(
        public string $manifestVerdict,
        public string $digestStatus,
        public string $schemaStatus,
        public string $canonicalStatus,
        public string $bindingStatus,
        public ?string $trustMode,
        public ?string $authenticity,
        public ?string $anchorId,
        public ?string $approvalRef,
        public ?string $manifestId,
        public ?string $expectedDigest,
        public ?string $actualDigest,
        public ?string $primaryFailureCode,
        public array $errors,
        public bool $diagnosticsTruncated,
        public ?Manifest $manifest,
    ) {
        $this->resultVersion = '1.0';
        $this->verdict = $manifestVerdict;
        $this->scope = 'MANIFEST_ONLY';
        $this->anchorHistoryStatus = 'NOT_EVALUATED';
        $this->fileInventoryStatus = 'NOT_EVALUATED';
        $this->filesystemIdentityStatus = 'NOT_EVALUATED';
        $this->executionAuthorization = 'NOT_EVALUATED';
        $this->canLaunchPoC = false;
    }

    /** Concrete final evaluator runs every gate; no supplied status/facts/DTO is trusted. */
    public static function fromVerification(ManifestDigestVerifier $verifier, mixed $context, mixed $candidateAnchor, string $rawBytes): self
    {
        return new self(...$verifier->evaluate($context, $candidateAnchor, $rawBytes));
    }

    public function __unserialize(array $data): void
    {
        throw new InvalidArgumentException('Manifest verification results cannot be unserialized.');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
