<?php

namespace App\Enums;

/**
 * A vevői rendelések feldolgozási, gyártási és kiszállítási
 * életciklusának állapotait határozza meg.
 */
enum CustomerOrderStatus: string
{
    /** A rendelés még előkészítés vagy módosítás alatt áll. */
    case Draft = 'draft';

    /** A rendelést visszaigazolták. */
    case Confirmed = 'confirmed';

    /** A rendelés anyagszükségletének tervezése folyamatban van. */
    case MaterialPlanning = 'material_planning';

    /** A gyártás a szükséges anyag rendelkezésre állására vár. */
    case WaitingForMaterial = 'waiting_for_material';

    /** A szükséges feltételek teljesülnek, a rendelés gyártásra kész. */
    case ReadyForProduction = 'ready_for_production';

    /** A rendelés gyártása folyamatban van. */
    case InProduction = 'in_production';

    /** A rendelés minőségellenőrzés alatt áll. */
    case QualityCheck = 'quality_check';

    /** A rendelés kiszállításra kész. */
    case ReadyToShip = 'ready_to_ship';

    /** A rendelés befejeződött. */
    case Completed = 'completed';

    /** A rendelést törölték, ezért további feldolgozása nem szükséges. */
    case Cancelled = 'cancelled';
}
