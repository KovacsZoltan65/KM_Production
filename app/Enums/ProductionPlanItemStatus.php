<?php

namespace App\Enums;

/**
 * A gyártási tervtételek tervezési és végrehajtási állapotait reprezentálja.
 */
enum ProductionPlanItemStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case Ready = 'ready';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
