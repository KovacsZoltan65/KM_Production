<?php

namespace App\Enums;

/**
 * A gyártási tervtételek tervezési és végrehajtási
 * életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a tervtétel még előkészítés alatt áll,
 * megtervezték, végrehajtásra kész, folyamatban van,
 * befejeződött vagy megszakították.
 */
enum ProductionPlanItemStatus: string
{
    /** A tervtétel még előkészítés vagy módosítás alatt áll. */
    case Draft = 'draft';

    /** A tervtételt megtervezték, de még nem áll készen a végrehajtásra. */
    case Planned = 'planned';

    /** A tervtétel készen áll a végrehajtás megkezdésére. */
    case Ready = 'ready';

    /** A tervtétel végrehajtása folyamatban van. */
    case InProgress = 'in_progress';

    /** A tervtétel végrehajtása befejeződött. */
    case Completed = 'completed';

    /** A tervtételt megszakították, ezért további végrehajtása nem szükséges. */
    case Cancelled = 'cancelled';
}