<?php

namespace App\Enums;

/**
 * A cikkek beszerzési és gyártási eredet szerinti üzleti típusait reprezentálja.
 */
enum ItemType: string
{
    case PurchasedMaterial = 'purchased_material';
    case ManufacturedPart = 'manufactured_part';
    case SemiFinishedProduct = 'semi_finished_product';
    case FinishedProduct = 'finished_product';

    /**
     * Megállapítja, hogy a cikktípus egyedi sorozatszámos nyomon követést igényel-e.
     */
    public function requiresSerialNumber(): bool
    {
        return $this !== self::PurchasedMaterial;
    }
}
