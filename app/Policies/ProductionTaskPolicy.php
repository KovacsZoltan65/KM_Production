<?php

namespace App\Policies;

use App\Models\ProductionTask;
use App\Models\User;

class ProductionTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('production-tasks.view');
    }

    public function view(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.view');
    }

    public function create(User $user): bool
    {
        return $user->can('production-tasks.create');
    }

    public function update(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.update');
    }

    public function delete(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.delete');
    }

    public function start(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.start');
    }

    public function finish(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.finish');
    }

    public function useMaterials(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.materials');
    }

    public function check(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.check');
    }
}
