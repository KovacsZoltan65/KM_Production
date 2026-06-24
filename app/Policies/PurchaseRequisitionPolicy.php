<?php

namespace App\Policies;

use App\Models\PurchaseRequisition;
use App\Models\User;

class PurchaseRequisitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('procurement.view');
    }

    public function view(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('procurement.view');
    }

    public function create(User $user): bool
    {
        return $user->can('procurement.create');
    }

    public function update(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('procurement.update');
    }

    public function delete(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('procurement.delete');
    }

    public function approve(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('procurement.approve');
    }

    public function generatePurchaseOrder(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('purchase-orders.generate');
    }
}
