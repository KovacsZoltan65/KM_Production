<?php

namespace App\Enums;

enum ProductionTaskStatus: string
{
    case Planned = 'planned';
    case Ready = 'ready';
    case InProgress = 'in_progress';
    case WaitingForCheck = 'waiting_for_check';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
