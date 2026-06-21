<?php

namespace App\Enums;

enum ProductionPlanItemStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case Ready = 'ready';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
