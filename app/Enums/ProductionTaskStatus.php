<?php

namespace App\Enums;

/**
 * A gyártási feladatok végrehajtási sorrendjének és minőségi lezárásának állapotait reprezentálja.
 */
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
