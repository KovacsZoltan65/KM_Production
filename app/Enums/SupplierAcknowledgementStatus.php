<?php

namespace App\Enums;

enum SupplierAcknowledgementStatus: string
{
    case Accepted = 'accepted';
    case AcceptedWithChanges = 'accepted_with_changes';
    case Rejected = 'rejected';
}
