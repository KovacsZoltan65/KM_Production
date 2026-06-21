<?php

namespace App\Enums;

enum PurchaseOrderItemStatus: string
{
    case Ordered = 'ordered';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';
}
