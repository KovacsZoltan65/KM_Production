<?php

namespace App\Policies;

use App\Models\Bom;
use App\Models\User;

class BomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('boms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('boms.create');
    }

    public function update(User $user, Bom $bom): bool
    {
        return $user->can('boms.update');
    }

    public function delete(User $user, Bom $bom): bool
    {
        return $user->can('boms.delete');
    }
}
