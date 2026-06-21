<?php

namespace App\Enums;

enum ProductionOrderStatus: string
{
    case Planned = 'planned';
    case Released = 'released';
    case InProgress = 'in_progress';
    case WaitingForCheck = 'waiting_for_check';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
