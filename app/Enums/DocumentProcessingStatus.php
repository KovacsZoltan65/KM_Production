<?php

namespace App\Enums;

/**
 * A dokumentumok automatizált feldolgozásának és emberi
 * felülvizsgálatának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a dokumentum feldolgozásra vár, feldolgozás alatt
 * áll, sikeresen feldolgozták, a feldolgozás sikertelen volt, vagy az
 * eredmény emberi felülvizsgálatot igényel.
 */
enum DocumentProcessingStatus: string
{
    /** A dokumentum automatizált feldolgozásra vár. */
    case Pending = 'pending';

    /** A dokumentum automatizált feldolgozása folyamatban van. */
    case Processing = 'processing';

    /** A dokumentum automatizált feldolgozása sikeresen befejeződött. */
    case Completed = 'completed';

    /** A dokumentum automatizált feldolgozása hiba miatt nem fejeződött be sikeresen. */
    case Failed = 'failed';

    /** A feldolgozás eredménye emberi felülvizsgálatot igényel. */
    case ReviewRequired = 'review_required';
}