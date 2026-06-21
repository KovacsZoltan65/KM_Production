<?php

namespace App\Enums;

enum PurchaseRequisitionItemStatus: string
{
    case Draft = 'draft';
    case Requested = 'requested';
    case Ordered = 'ordered';
    case Cancelled = 'cancelled';
}
