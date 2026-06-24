<?php

namespace App\Repositories\Contracts;

use App\Models\BomItem;
use App\Models\MaterialRequirement;
use App\Models\ProductionOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MaterialRequirementRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateShortages(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function updateForProductionOrderBomItem(
        ProductionOrder $productionOrder,
        BomItem $bomItem,
        float $requiredQuantity,
        float $availableQuantity,
        float $reservedQuantity,
        float $missingQuantity,
        string $status
    ): MaterialRequirement;
}
