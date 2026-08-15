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
