<?php

namespace App\Enums;

enum StockReservationStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Consumed = 'consumed';
    case Cancelled = 'cancelled';
}
