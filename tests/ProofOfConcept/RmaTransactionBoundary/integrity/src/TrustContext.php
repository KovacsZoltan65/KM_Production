<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

/** Expected values supplied independently by the trusted caller, never by a candidate. */
final readonly class TrustContext
{
    public function __construct(
        public string $trustMode,
        public string $selectionOrigin,
        public string $selectionRef,
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

    public function expectedAnchor(): \stdClass
    {
        return (object) [
            'anchorVersion' => $this->anchorVersion,
            'anchorId' => $this->anchorId,
            'manifestId' => $this->manifestId,
            'manifestSha256' => $this->manifestSha256,
            'profileId' => $this->profileId,
            'profileSha256' => $this->profileSha256,
            'approvalRef' => $this->approvalRef,
            'verifierReleaseId' => $this->verifierReleaseId,
            'status' => $this->status,
        ];
    }
}
