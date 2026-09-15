<?php

namespace App\Enums;

/**
 * A gyártási feladatok végrehajtási és minőségi
 * életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a gyártási feladat még tervezett,
 * végrehajtásra kész, folyamatban van, ellenőrzésre vár,
 * befejeződött, elutasították vagy megszakították.
 */
enum ProductionTaskStatus: string
{
    /** A gyártási feladatot megtervezték, de még nem áll készen a végrehajtásra. */
    case Planned = 'planned';

    /** A gyártási feladat készen áll a végrehajtás megkezdésére. */
    case Ready = 'ready';

    /** A gyártási feladat végrehajtása folyamatban van. */
    case InProgress = 'in_progress';

    /** A gyártási feladat végrehajtása befejeződött, és ellenőrzésre vár. */
    case WaitingForCheck = 'waiting_for_check';

    /** A gyártási feladat teljesítése befejeződött. */
    case Completed = 'completed';

    /** A gyártási feladat eredményét az ellenőrzés során elutasították. */
    case Rejected = 'rejected';

    /** A gyártási feladatot megszakították, ezért további végrehajtása nem szükséges. */
    case Cancelled = 'cancelled';
}