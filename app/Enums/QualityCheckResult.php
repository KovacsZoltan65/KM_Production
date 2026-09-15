<?php

namespace App\Enums;

/**
 * A beszerzési igények igénylési, jóváhagyási és megrendelési
 * életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a beszerzési igény még előkészítés alatt áll,
 * benyújtották, jóváhagyták, megrendelési szakaszba került
 * vagy megszakították.
 */
enum PurchaseRequisitionStatus: string
{
    /** A beszerzési igény még előkészítés vagy módosítás alatt áll. */
    case Draft = 'draft';

    /** A beszerzési igényt benyújtották további feldolgozásra. */
    case Requested = 'requested';

    /** A beszerzési igényt jóváhagyták további végrehajtásra. */
    case Approved = 'approved';

    /** A beszerzési igény megrendelési szakaszba került. */
    case Ordered = 'ordered';

    /** A beszerzési igényt megszakították, ezért további feldolgozása nem szükséges. */
    case Cancelled = 'cancelled';
}