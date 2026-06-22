<?php

namespace App\Policies;

use App\Models\FactoryUnit;
use App\Models\User;

class FactoryUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('factory-units.view');
    }

    public function create(User $user): bool
    {
        return $user->can('factory-units.create');
    }

    public function update(User $user, FactoryUnit $factoryUnit): bool
    {
        return $user->can('factory-units.update');
    }

    public function delete(User $user, FactoryUnit $factoryUnit): bool
    {
        return $user->can('factory-units.delete');
    }
}
