<?php

namespace App\Enums;

enum LocationType: string
{
    case Warehouse = 'warehouse';
    case Workshop = 'workshop';
    case QualityArea = 'quality_area';
    case FinishedGoods = 'finished_goods';
    case Scrap = 'scrap';
}
