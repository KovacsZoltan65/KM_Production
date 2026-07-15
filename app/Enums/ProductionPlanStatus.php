<?php

namespace App\Enums;

/**
 * A gyártási tervek számítási, jóváhagyási és végrehajtási életciklusát reprezentálja.
 */
enum ProductionPlanStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
