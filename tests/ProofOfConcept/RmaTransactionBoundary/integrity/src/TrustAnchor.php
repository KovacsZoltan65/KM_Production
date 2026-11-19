<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

/** Data-only record; construction alone establishes neither validity nor provenance. */
final readonly class TrustAnchor
{
    public function __construct(
        public string $anchorVersion,
        public string $anchorId,
        public string $manifestId,
        public string $manifestSha256,
        public string $profileId,
        public string $profileSha256,
        public string $approvalRef,
        public string $verifierReleaseId,
        public string $status,
    ) {}
}
