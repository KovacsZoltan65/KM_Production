<?php

namespace App\Enums;

enum ProductionPlanStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
