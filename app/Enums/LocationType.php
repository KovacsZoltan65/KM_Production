<?php

namespace App\Enums;

/**
 * A készletmozgásokban és gyártási folyamatokban használt
 * fizikai helyek rendeltetését határozza meg.
 *
 * A helytípus segítségével megkülönböztethetők többek között
 * a raktározási, gyártási, minőség-ellenőrzési, késztermék-tárolási
 * és selejtkezelési területek.
 */
enum LocationType: string
{
    /** Anyagok, alkatrészek vagy egyéb készletek tárolására szolgáló raktári hely. */
    case Warehouse = 'warehouse';

    /** Gyártási vagy megmunkálási tevékenység végzésére szolgáló terület. */
    case Workshop = 'workshop';

    /** Minőség-ellenőrzéshez kapcsolódó tevékenységek számára kijelölt terület. */
    case QualityArea = 'quality_area';

    /** Elkészült termékek tárolására kijelölt terület. */
    case FinishedGoods = 'finished_goods';

    /** Selejtezett vagy selejtkezelésre váró tételek számára kijelölt terület. */
    case Scrap = 'scrap';
}