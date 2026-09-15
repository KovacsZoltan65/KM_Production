<?php

namespace App\Enums;

/**
 * Az ellátási javaslat döntés-előkészítési életciklusának
 * állapotait és megengedett állapotátmeneteit határozza meg.
 *
 * Az ellátási javaslat tervezési artefaktum, amely előkészíti az
 * ellátási döntést. Az állapot jelzi, hogy a javaslat még szerkeszthető,
 * döntésre előterjesztett, jóváhagyott, elutasított vagy megszüntetett.
 */
enum SupplyProposalStatus: string
{
    /** A javaslat még előkészítés alatt áll és szerkeszthető. */
    case Draft = 'draft';

    /** A javaslatot döntésre előterjesztették. */
    case Proposed = 'proposed';

    /** A javaslatot jóváhagyták. */
    case Approved = 'approved';

    /** A javaslatot elutasították. */
    case Rejected = 'rejected';

    /** A javaslatot megszüntették, ezért további feldolgozása nem szükséges. */
    case Cancelled = 'cancelled';

    /**
     * Megállapítja, hogy a javaslat az aktuális állapotában szerkeszthető-e.
     *
     * A javaslat kizárólag tervezet állapotban módosítható.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Megállapítja, hogy az aktuális állapotból engedélyezett-e
     * a megadott célállapotba történő átmenet.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => \in_array($target, [self::Proposed, self::Cancelled], true),
            self::Proposed => \in_array(
                $target,
                [self::Approved, self::Rejected, self::Cancelled],
                true,
            ),
            self::Approved => $target === self::Cancelled,
            self::Rejected, self::Cancelled => false,
        };
    }
}