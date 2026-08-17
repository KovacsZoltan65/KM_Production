<?php

namespace App\Support\Procurement;

final readonly class ReplenishmentQuantityResult
{
    public function __construct(
        public int $itemId,
        public int $itemSupplierId,
        public string $baseRequiredQuantity,
        public string $adjustedQuantity,
        public string $excessQuantity,
        public string $baseUnit,
        public ?string $minimumOrderQuantity,
        public ?string $orderMultiple,
        public string $strategy,
        public string $purchaseUnit,
        public string $conversionFactor,
    ) {}
}
