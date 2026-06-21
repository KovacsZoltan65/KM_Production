<?php

namespace App\Enums;

enum ItemType: string
{
    case PurchasedMaterial = 'purchased_material';
    case ManufacturedPart = 'manufactured_part';
    case SemiFinishedProduct = 'semi_finished_product';
    case FinishedProduct = 'finished_product';

    public function requiresSerialNumber(): bool
    {
        return $this !== self::PurchasedMaterial;
    }
}
