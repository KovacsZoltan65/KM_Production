<?php

namespace App\Enums;

enum SupplierAcknowledgementQuantityVariance: string
{
    case Matched = 'matched';
    case Reduced = 'reduced';
    case Increased = 'increased';
    case Rejected = 'rejected';
}
