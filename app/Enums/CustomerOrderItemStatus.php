<?php

namespace App\Enums;

/**
 * Az AI-feldolgozási futások végrehajtási és felülvizsgálati
 * állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a feldolgozás várakozik, folyamatban van,
 * sikeresen befejeződött, hibával leállt, vagy emberi felülvizsgálatot igényel.
 */
enum AiProcessingRunStatus: string
{
    /** A feldolgozás végrehajtásra vár. */
    case Pending = 'pending';

    /** A feldolgozás jelenleg folyamatban van. */
    case Running = 'running';

    /** A feldolgozás sikeresen befejeződött. */
    case Completed = 'completed';

    /** A feldolgozás hiba miatt nem fejeződött be sikeresen. */
    case Failed = 'failed';

    /** A feldolgozás eredménye emberi felülvizsgálatot igényel. */
    case ReviewRequired = 'review_required';
}