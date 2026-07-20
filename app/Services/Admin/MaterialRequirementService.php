<?php

namespace App\Services\Admin;

use App\Enums\MaterialRequirementStatus;
use App\Models\BomItem;
use App\Models\MaterialRequirement;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Repositories\Contracts\MaterialRequirementRepositoryInterface;
use App\Repositories\Contracts\StockBalanceRepositoryInterface;
use App\Repositories\Contracts\StockReservationRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use Illuminate\Support\Collection;

class MaterialRequirementService
{
    public function __construct(
        private readonly MaterialRequirementRepositoryInterface $materialRequirements,
        private readonly StockBalanceRepositoryInterface $stockBalances,
        private readonly StockReservationRepositoryInterface $stockReservations,
        private readonly AuditLogService $auditLogService,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    /**
     * @return Collection<int, MaterialRequirement>
     */
    public function calculateForProductionOrder(ProductionOrder $productionOrder, ?User $causer = null): Collection
    {
        $productionOrder->loadMissing(['bom.bomItems', 'customerOrderItem']);

        $requirements = $productionOrder->bom?->bomItems
            ->map(fn (BomItem $bomItem): MaterialRequirement => $this->calculateBomItem($productionOrder, $bomItem))
            ?? collect();

        $this->auditLogService->log('material_requirements_calculated', $productionOrder, [
            'requirements_count' => $requirements->count(),
        ], $causer);
        $this->cacheInvalidator->inventoryChanged();

        return $requirements;
    }

    private function calculateBomItem(ProductionOrder $productionOrder, BomItem $bomItem): MaterialRequirement
    {
        $requiredQuantity = (float) $productionOrder->quantity * (float) $bomItem->quantity;
        $stockQuantity = $this->stockBalances->totalQuantityForItem($bomItem->item_id);
        $activeReservedQuantity = $this->stockReservations->activeReservedQuantity($bomItem->item_id);
        $availableQuantity = max(0, $stockQuantity - $activeReservedQuantity);
        $reservedForDemand = $this->stockReservations->activeReservedQuantity(
            $bomItem->item_id,
            null,
            null,
            $productionOrder->customer_order_item_id,
            $productionOrder->id
        );
        $missingQuantity = max(0, $requiredQuantity - $reservedForDemand - $availableQuantity);

        return $this->materialRequirements->updateForProductionOrderBomItem(
            $productionOrder,
            $bomItem,
            $requiredQuantity,
            $availableQuantity,
            $reservedForDemand,
            $missingQuantity,
            $this->statusFor($requiredQuantity, $availableQuantity, $reservedForDemand, $missingQuantity)->value
        );
    }

    private function statusFor(
        float $requiredQuantity,
        float $availableQuantity,
        float $reservedQuantity,
        float $missingQuantity
    ): MaterialRequirementStatus {
        if ($missingQuantity > 0 && $reservedQuantity > 0) {
            return MaterialRequirementStatus::PartiallyAvailable;
        }

        if ($missingQuantity > 0) {
            return MaterialRequirementStatus::Missing;
        }

        if ($reservedQuantity >= $requiredQuantity) {
            return MaterialRequirementStatus::Reserved;
        }

        if ($availableQuantity >= $requiredQuantity) {
            return MaterialRequirementStatus::Calculated;
        }

        return MaterialRequirementStatus::PartiallyAvailable;
    }
}
