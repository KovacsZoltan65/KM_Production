<?php

namespace App\Enums;

/**
 * A beszerzési igények jóváhagyási és megrendelési életciklusát reprezentálja.
 */
enum PurchaseRequisitionStatus: string
{
    case Draft = 'draft';
    case Requested = 'requested';
    case Approved = 'approved';
    case Ordered = 'ordered';
    case Cancelled = 'cancelled';
}
