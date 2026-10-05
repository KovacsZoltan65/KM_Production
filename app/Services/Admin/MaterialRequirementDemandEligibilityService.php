<?php

namespace App\Services\Admin;

use App\Enums\CustomerOrderItemStatus;
use App\Enums\CustomerOrderStatus;
use App\Enums\ProductionOrderStatus;
use App\Models\MaterialRequirement;

class MaterialRequirementDemandEligibilityService
{
    /** Evaluates current demand sources under ADR 0016, independently of MR snapshot status. */
    public function isEligible(MaterialRequirement $requirement): bool
    {
        if ($requirement->trashed()) {
            return false;
        }

        $item = $requirement->customerOrderItem;
        $order = $item?->customerOrder;

        if ($item === null || $item->trashed() || $order === null || $order->trashed()
            || in_array($item->status, [CustomerOrderItemStatus::Completed, CustomerOrderItemStatus::Cancelled], true)
            || ! in_array($order->status, [
                CustomerOrderStatus::Confirmed,
                CustomerOrderStatus::MaterialPlanning,
                CustomerOrderStatus::WaitingForMaterial,
                CustomerOrderStatus::ReadyForProduction,
                CustomerOrderStatus::InProduction,
                CustomerOrderStatus::QualityCheck,
                CustomerOrderStatus::ReadyToShip,
            ], true)) {
            return false;
        }

        // Only two null foreign keys prove completely absent legacy lineage.
        // A soft-deleted production parent may resolve to null despite a non-null key.
        if ($requirement->production_order_id === null && $requirement->bom_item_id === null) {
            return true;
        }

        if ($requirement->production_order_id === null || $requirement->bom_item_id === null) {
            return false;
        }

        $production = $requirement->productionOrder;
        $bomItem = $requirement->bomItem;

        return $production !== null && ! $production->trashed() && $bomItem !== null
            && $production->customer_order_item_id === $requirement->customer_order_item_id
            && $production->bom_id === $bomItem->bom_id
            && $bomItem->item_id === $requirement->required_item_id
            && in_array($production->status, [
                ProductionOrderStatus::Planned,
                ProductionOrderStatus::Released,
                ProductionOrderStatus::InProgress,
                ProductionOrderStatus::WaitingForCheck,
            ], true);
    }
}
