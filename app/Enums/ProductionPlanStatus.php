<?php

namespace App\Enums;

/**
 * A gyártási tervek számítási, jóváhagyási és végrehajtási
 * életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a gyártási terv még előkészítés alatt áll,
 * kiszámították, jóváhagyták, végrehajtás alatt áll,
 * befejeződött vagy megszakították.
 */
enum ProductionPlanStatus: string
{
    /** A gyártási terv még előkészítés vagy módosítás alatt áll. */
    case Draft = 'draft';

    /** A gyártási terv számítása megtörtént, de még nem hagyták jóvá. */
    case Calculated = 'calculated';

    /** A gyártási tervet jóváhagyták végrehajtásra. */
    case Approved = 'approved';

    /** A gyártási terv végrehajtása folyamatban van. */
    case InProgress = 'in_progress';

    /** A gyártási terv végrehajtása befejeződött. */
    case Completed = 'completed';

    /** A gyártási tervet megszakították, ezért további végrehajtása nem szükséges. */
    case Cancelled = 'cancelled';
}