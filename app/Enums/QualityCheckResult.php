<?php

namespace App\Enums;

enum QualityCheckResult: string
{
    case Accepted = 'accepted';
    case ReworkRequired = 'rework_required';
    case Rejected = 'rejected';
}
