<?php

namespace App\Policies;

use App\Models\MaterialRequirement;
use App\Models\User;

class MaterialRequirementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, MaterialRequirement $materialRequirement): bool
    {
        return $user->can('inventory.view');
    }
}
