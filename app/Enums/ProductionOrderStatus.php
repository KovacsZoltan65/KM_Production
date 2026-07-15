<?php

namespace App\Enums;

/**
 * A gyártási rendelések felszabadítási, végrehajtási és lezárási állapotait reprezentálja.
 */
enum ProductionOrderStatus: string
{
    case Planned = 'planned';
    case Released = 'released';
    case InProgress = 'in_progress';
    case WaitingForCheck = 'waiting_for_check';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
