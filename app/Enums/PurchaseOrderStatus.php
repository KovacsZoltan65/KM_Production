<?php

namespace App\Enums;

/**
 * A beszerzési rendelések kiadási és beérkezési életciklusát reprezentálja.
 */
enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Ordered = 'ordered';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';
}
