<?php

namespace App\Enums;

/**
 * A gyártási és vevői igényekhez létrehozott
 * készletfoglalások életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a készletfoglalás aktív, felszabadított,
 * felhasznált vagy megszüntetett állapotban van.
 */
enum StockReservationStatus: string
{
    /** A készletfoglalás aktív, ezért a lefoglalt mennyiség más igény számára nem elérhető. */
    case Active = 'active';

    /** A készletfoglalást feloldották, így a mennyiség ismét rendelkezésre állhat más igények számára. */
    case Released = 'released';

    /** A lefoglalt mennyiséget a foglalás céljának megfelelően felhasználták. */
    case Consumed = 'consumed';

    /** A készletfoglalást megszüntették anélkül, hogy annak teljesítése megtörtént volna. */
    case Cancelled = 'cancelled';
}