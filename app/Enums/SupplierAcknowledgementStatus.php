<?php

namespace App\Enums;

/**
 * A beszállítói visszaigazolások összesített
 * üzleti eredményállapotait határozza meg.
 *
 * Az állapot jelzi, hogy a beszállító a beszerzési rendelést
 * változtatás nélkül elfogadta, módosításokkal vállalta,
 * vagy elutasította.
 */
enum SupplierAcknowledgementStatus: string
{
    /** A beszállító a beszerzési rendelést változtatás nélkül elfogadta. */
    case Accepted = 'accepted';

    /** A beszállító a beszerzési rendelést a visszaigazolásban jelzett változtatásokkal vállalta. */
    case AcceptedWithChanges = 'accepted_with_changes';

    /** A beszállító a beszerzési rendelés teljesítését elutasította. */
    case Rejected = 'rejected';
}