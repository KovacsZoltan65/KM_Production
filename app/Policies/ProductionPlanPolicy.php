<?php

namespace App\Policies;

use App\Models\ProductionPlan;
use App\Models\User;

class ProductionPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('production-plans.view');
    }

    public function view(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-plans.view');
    }

    public function create(User $user): bool
    {
        return $user->can('production-plans.create');
    }

    public function update(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-plans.update');
    }

    public function delete(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-plans.delete');
    }

    public function approve(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-plans.approve');
    }

    public function generateProductionOrders(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-orders.generate');
    }
}
