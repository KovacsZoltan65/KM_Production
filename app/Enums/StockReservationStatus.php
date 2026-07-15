<?php

namespace App\Enums;

/**
 * A gyártási és vevői igényekhez létrehozott készletfoglalások életciklusát reprezentálja.
 */
enum StockReservationStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Consumed = 'consumed';
    case Cancelled = 'cancelled';
}
