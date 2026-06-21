<?php

namespace App\Enums;

enum PurchaseRequisitionStatus: string
{
    case Draft = 'draft';
    case Requested = 'requested';
    case Approved = 'approved';
    case Ordered = 'ordered';
    case Cancelled = 'cancelled';
}
