<?php

namespace App\Repositories\Contracts;

use App\Models\MaterialRequirement;
use App\Models\PurchaseRequisition;
use Illuminate\Support\Collection;

interface PurchaseRequisitionRepositoryInterface extends AdminRepositoryInterface
{
    public function findForShow(PurchaseRequisition $purchaseRequisition): PurchaseRequisition;

    /**
     * @return Collection<int, MaterialRequirement>
     */
    public function missingMaterialRequirements(): Collection;
}
