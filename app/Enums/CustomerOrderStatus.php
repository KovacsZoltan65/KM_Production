<?php

namespace App\Enums;

/**
 * A vevői rendeléstételek tervezési és gyártási életciklusának
 * állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a rendeléstétel még előkészítés alatt áll,
 * megtervezték, anyagra vár, gyártásra kész, gyártás alatt áll,
 * befejeződött vagy megszakították.
 */
enum CustomerOrderItemStatus: string
{
    /** A rendeléstétel még előkészítés vagy módosítás alatt áll. */
    case Draft = 'draft';

    /** A rendeléstétel gyártását megtervezték. */
    case Planned = 'planned';

    /** A gyártás a szükséges anyag rendelkezésre állására vár. */
    case WaitingForMaterial = 'waiting_for_material';

    /** A szükséges feltételek teljesülnek, a rendeléstétel gyártásra kész. */
    case ReadyForProduction = 'ready_for_production';

    /** A rendeléstétel gyártása folyamatban van. */
    case InProduction = 'in_production';

    /** A rendeléstétel gyártása befejeződött. */
    case Completed = 'completed';

    /** A rendeléstételt törölték, ezért további feldolgozása nem szükséges. */
    case Cancelled = 'cancelled';
}