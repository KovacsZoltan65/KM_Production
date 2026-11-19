<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

/** Fixed injected selection: no candidate/manifest selector, lookup, fallback or I/O. */
final readonly class AnchorProvider
{
    public function __construct(private ?TrustAnchor $selectedAnchor) {}

    public function selectedAnchor(): ?TrustAnchor
    {
        return $this->selectedAnchor;
    }
}
