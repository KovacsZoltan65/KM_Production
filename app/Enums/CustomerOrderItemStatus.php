<?php

namespace App\Enums;

/**
 * A vevői rendeléstételek tervezési és gyártási életciklusának állapotait reprezentálja.
 */
enum CustomerOrderItemStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case WaitingForMaterial = 'waiting_for_material';
    case ReadyForProduction = 'ready_for_production';
    case InProduction = 'in_production';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
