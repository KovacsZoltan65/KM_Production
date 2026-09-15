<?php

namespace App\Enums;

/**
 * A cikkek beszerzési és gyártási eredet szerinti
 * üzleti típusait határozza meg.
 *
 * A cikktípus megkülönbözteti a külső forrásból beszerzett anyagokat,
 * a saját gyártású alkatrészeket, a félkész termékeket és a késztermékeket.
 * A típus egyben meghatározza az egyedi sorozatszámos nyomon követés
 * követelményét is.
 */
enum ItemType: string
{
    /** Külső forrásból beszerzett, a gyártás során felhasználható anyag. */
    case PurchasedMaterial = 'purchased_material';

    /** Saját gyártásban előállított alkatrész. */
    case ManufacturedPart = 'manufactured_part';

    /** Saját gyártásban előállított, további feldolgozásra szánt félkész termék. */
    case SemiFinishedProduct = 'semi_finished_product';

    /** Saját gyártásban előállított késztermék. */
    case FinishedProduct = 'finished_product';

    /**
     * Megállapítja, hogy a cikktípus egyedi sorozatszámos
     * nyomon követést igényel-e.
     *
     * A beszerzett anyagok kivételével minden cikktípushoz
     * egyedi sorozatszámos nyomon követés szükséges.
     */
    public function requiresSerialNumber(): bool
    {
        return $this !== self::PurchasedMaterial;
    }
}