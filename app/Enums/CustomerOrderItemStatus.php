<?php

namespace App\Enums;

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
