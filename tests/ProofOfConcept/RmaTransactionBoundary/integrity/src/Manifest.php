<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

/** Validated data only; this DTO carries no integrity or execution authorization. */
final readonly class Manifest
{
    /**
     * @param  list<array{path: string, sizeBytes: int, sha256: string, category: string}>  $files
     * @param  list<array{path: string, sizeBytes: int, sha256: string, provenanceRef: string}>  $historicalArtifacts
     * @param  array{baselineCommit: string, profileId: string, profileSha256: string, provenanceRef: string}  $verificationMetadata
     */
    public function __construct(
        public string $formatId,
        public string $schemaVersion,
        public string $manifestId,
        public string $pocVersion,
        public string $createdAt,
        public bool $exampleOnly,
        public array $files,
        public array $historicalArtifacts,
        public array $verificationMetadata,
    ) {}
}
