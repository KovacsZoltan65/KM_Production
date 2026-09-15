<?php

namespace App\Enums;

/**
 * A beszerzési igénytételek igénylési és megrendelési
 * életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a beszerzési igénytétel még előkészítés alatt áll,
 * igényelt, megrendelt vagy megszakított állapotban van.
 */
enum PurchaseRequisitionItemStatus: string
{
    /** A beszerzési igénytétel még előkészítés vagy módosítás alatt áll. */
    case Draft = 'draft';

    /** A beszerzési igénytételt beszerzésre igényelték. */
    case Requested = 'requested';

    /** A beszerzési igénytétel megrendelési szakaszba került. */
    case Ordered = 'ordered';

    /** A beszerzési igénytételt megszakították, ezért további feldolgozása nem szükséges. */
    case Cancelled = 'cancelled';
}