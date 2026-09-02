<?php

namespace App\Enums;

enum SupplierAcknowledgementDeliveryDateVariance: string
{
    case Matched = 'matched';
    case Earlier = 'earlier';
    case Later = 'later';
    case NotConfirmed = 'not_confirmed';
    case NoBuyerBaseline = 'no_buyer_baseline';
    case Rejected = 'rejected';
}
