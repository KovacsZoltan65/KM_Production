<?php

namespace App\Enums;

/**
 * Az egyedileg nyomon követett cikkpéldányok gyártási, készlet- és minőségi állapotait reprezentálja.
 */
enum ItemInstanceStatus: string
{
    case Planned = 'planned';
    case InProduction = 'in_production';
    case WaitingForCheck = 'waiting_for_check';
    case Checked = 'checked';
    case Rejected = 'rejected';
    case InStock = 'in_stock';
    case Consumed = 'consumed';
    case Shipped = 'shipped';
    case Scrapped = 'scrapped';
}
