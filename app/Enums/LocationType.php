<?php

namespace App\Enums;

/**
 * A készletmozgásokban és gyártásban használt fizikai helyek rendeltetését reprezentálja.
 */
enum LocationType: string
{
    case Warehouse = 'warehouse';
    case Workshop = 'workshop';
    case QualityArea = 'quality_area';
    case FinishedGoods = 'finished_goods';
    case Scrap = 'scrap';
}
