<?php

namespace App\Enums;

/**
 * A gyártási rendelések felszabadítási, végrehajtási és
 * lezárási életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a gyártási rendelés még tervezett,
 * végrehajtásra felszabadított, folyamatban van, ellenőrzésre vár,
 * befejeződött vagy megszakították.
 */
enum ProductionOrderStatus: string
{
    /** A gyártási rendelést megtervezték, de még nem szabadították fel végrehajtásra. */
    case Planned = 'planned';

    /** A gyártási rendelést felszabadították végrehajtásra. */
    case Released = 'released';

    /** A gyártási rendelés végrehajtása folyamatban van. */
    case InProgress = 'in_progress';

    /** A gyártási rendelés végrehajtása befejeződött, és ellenőrzésre vár. */
    case WaitingForCheck = 'waiting_for_check';

    /** A gyártási rendelés végrehajtása és szükséges ellenőrzése befejeződött. */
    case Completed = 'completed';

    /** A gyártási rendelést megszakították, ezért további végrehajtása nem szükséges. */
    case Cancelled = 'cancelled';
}