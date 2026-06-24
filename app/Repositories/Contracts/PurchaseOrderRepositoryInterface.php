<?php

namespace App\Repositories\Contracts;

use App\Models\PurchaseOrder;

interface PurchaseOrderRepositoryInterface extends AdminRepositoryInterface
{
    public function findForShow(PurchaseOrder $purchaseOrder): PurchaseOrder;
}
