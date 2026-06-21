<?php

namespace App\Enums;

enum CustomerOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case MaterialPlanning = 'material_planning';
    case WaitingForMaterial = 'waiting_for_material';
    case ReadyForProduction = 'ready_for_production';
    case InProduction = 'in_production';
    case QualityCheck = 'quality_check';
    case ReadyToShip = 'ready_to_ship';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
