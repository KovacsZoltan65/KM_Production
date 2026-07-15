<?php

namespace App\Enums;

/**
 * A beszerzési igénytételek igénylési és megrendelési állapotait reprezentálja.
 */
enum PurchaseRequisitionItemStatus: string
{
    case Draft = 'draft';
    case Requested = 'requested';
    case Ordered = 'ordered';
    case Cancelled = 'cancelled';
}
