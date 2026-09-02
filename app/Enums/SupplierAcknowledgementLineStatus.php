<?php

namespace App\Enums;

enum SupplierAcknowledgementLineStatus: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
