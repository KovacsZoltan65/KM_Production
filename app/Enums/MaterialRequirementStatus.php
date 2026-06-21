<?php

namespace App\Enums;

enum MaterialRequirementStatus: string
{
    case Calculated = 'calculated';
    case Reserved = 'reserved';
    case PartiallyAvailable = 'partially_available';
    case Missing = 'missing';
    case Ordered = 'ordered';
    case Received = 'received';
    case Cancelled = 'cancelled';
}
