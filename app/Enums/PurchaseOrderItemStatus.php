<?php

namespace App\Enums;

/**
 * A beszerzési rendeléstételek mennyiségi beérkezési állapotait reprezentálja.
 */
enum PurchaseOrderItemStatus: string
{
    case Ordered = 'ordered';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';
}
