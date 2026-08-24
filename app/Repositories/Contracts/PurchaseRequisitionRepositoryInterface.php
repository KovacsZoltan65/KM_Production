<?php

namespace App\Repositories\Contracts;

use App\Models\MaterialRequirement;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\PurchaseRequisitionItemProposalSource;
use Illuminate\Support\Collection;

interface PurchaseRequisitionRepositoryInterface extends AdminRepositoryInterface
{
    public function findForShow(PurchaseRequisition $purchaseRequisition): PurchaseRequisition;

    public function findForExecutionReadiness(PurchaseRequisition $purchaseRequisition): PurchaseRequisition;

    public function findForSupplierSelection(PurchaseRequisition $purchaseRequisition): PurchaseRequisition;

    public function lockForSupplierSelection(int $requisitionId): PurchaseRequisition;

    public function assignSupplier(PurchaseRequisition $requisition, int $supplierId): PurchaseRequisition;

    public function lockForReplenishment(int $requisitionId): PurchaseRequisition;

    /** @param array<string, mixed> $attributes */
    public function updateItemReplenishment(PurchaseRequisitionItem $item, array $attributes): PurchaseRequisitionItem;

    /** @param array<string, mixed> $attributes */
    public function createDraft(array $attributes): PurchaseRequisition;

    /** @param array<string, mixed> $attributes */
    public function createItem(PurchaseRequisition $requisition, array $attributes): PurchaseRequisitionItem;

    /** @param array<string, mixed> $attributes */
    public function createProposalSource(PurchaseRequisitionItem $item, array $attributes): PurchaseRequisitionItemProposalSource;

    /**
     * @return Collection<int, MaterialRequirement>
     */
    public function missingMaterialRequirements(): Collection;
}
