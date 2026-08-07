<?php

namespace App\Enums;

/** A planning javaslat döntés-előkészítési életciklusa. */
enum SupplyProposalStatus: string
{
    case Draft = 'draft';
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => \in_array($target, [self::Proposed, self::Cancelled], true),
            self::Proposed => \in_array($target, [self::Approved, self::Rejected, self::Cancelled], true),
            self::Approved => $target === self::Cancelled,
            self::Rejected, self::Cancelled => false,
        };
    }
}
