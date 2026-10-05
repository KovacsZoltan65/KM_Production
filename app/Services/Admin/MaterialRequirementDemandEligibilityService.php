<?php

namespace App\Services\Admin;

use App\Enums\CustomerOrderItemStatus;
use App\Enums\CustomerOrderStatus;
use App\Enums\MaterialRequirementSourceValidity as Validity;
use App\Enums\ProductionOrderStatus;
use App\Models\MaterialRequirement;
use App\Support\MaterialPlanning\MaterialRequirementSourceAssessment;

class MaterialRequirementDemandEligibilityService
{
    public function isEligible(MaterialRequirement $requirement): bool
    {
        return $this->assessSource($requirement)->validity === Validity::Valid;
    }

    /** ADR 0016: proven invalid facts precede incomplete or contradictory lineage. */
    public function assessSource(MaterialRequirement $requirement): MaterialRequirementSourceAssessment
    {
        $item = $requirement->customerOrderItem;
        $order = $item?->customerOrder;
        $production = $requirement->production_order_id === null ? null : $requirement->productionOrder;

        $invalidReason = match (true) {
            $requirement->trashed() => 'material_requirement_deleted',
            $item?->trashed() === true => 'customer_order_item_deleted',
            $order?->trashed() === true => 'customer_order_deleted',
            $order?->status === CustomerOrderStatus::Draft => 'customer_order_draft',
            in_array($order?->status, [CustomerOrderStatus::Completed, CustomerOrderStatus::Cancelled], true) => 'customer_order_terminal',
            in_array($item?->status, [CustomerOrderItemStatus::Completed, CustomerOrderItemStatus::Cancelled], true) => 'customer_order_item_terminal',
            $production?->trashed() === true => 'production_order_deleted',
            in_array($production?->status, [ProductionOrderStatus::Completed, ProductionOrderStatus::Cancelled], true) => 'production_order_terminal',
            default => null,
        };

        if ($invalidReason !== null) {
            return new MaterialRequirementSourceAssessment(Validity::Invalid, $invalidReason);
        }

        if ($item === null || $order === null) {
            return new MaterialRequirementSourceAssessment(Validity::Undetermined, 'customer_source_unavailable');
        }

        if (! in_array($order->status, [
            CustomerOrderStatus::Confirmed, CustomerOrderStatus::MaterialPlanning,
            CustomerOrderStatus::WaitingForMaterial, CustomerOrderStatus::ReadyForProduction,
            CustomerOrderStatus::InProduction, CustomerOrderStatus::QualityCheck, CustomerOrderStatus::ReadyToShip,
        ], true)) {
            return new MaterialRequirementSourceAssessment(Validity::Undetermined, 'customer_lifecycle_unproven');
        }

        // Foreign keys distinguish absent legacy lineage from an unavailable production parent.
        if ($requirement->production_order_id === null && $requirement->bom_item_id === null) {
            return new MaterialRequirementSourceAssessment(Validity::Valid, 'legacy_production_lineage', true);
        }

        if ($requirement->production_order_id === null || $requirement->bom_item_id === null) {
            return new MaterialRequirementSourceAssessment(Validity::Undetermined, 'partial_production_lineage');
        }

        $bomItem = $requirement->bomItem;
        if ($production === null || $bomItem === null) {
            return new MaterialRequirementSourceAssessment(Validity::Undetermined, 'production_source_unavailable');
        }

        if ($production->customer_order_item_id !== $requirement->customer_order_item_id
            || $production->bom_id !== $bomItem->bom_id
            || $bomItem->item_id !== $requirement->required_item_id) {
            return new MaterialRequirementSourceAssessment(Validity::Undetermined, 'contradictory_production_lineage');
        }

        if (! in_array($production->status, [
            ProductionOrderStatus::Planned, ProductionOrderStatus::Released,
            ProductionOrderStatus::InProgress, ProductionOrderStatus::WaitingForCheck,
        ], true)) {
            return new MaterialRequirementSourceAssessment(Validity::Undetermined, 'production_lifecycle_unproven');
        }

        return new MaterialRequirementSourceAssessment(Validity::Valid, 'complete_production_lineage');
    }
}
