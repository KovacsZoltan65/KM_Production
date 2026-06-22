<?php

namespace App\Policies;

use App\Models\ProfessionalRole;
use App\Models\User;

class ProfessionalRolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('professional-roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('professional-roles.create');
    }

    public function update(User $user, ProfessionalRole $professionalRole): bool
    {
        return $user->can('professional-roles.update');
    }

    public function delete(User $user, ProfessionalRole $professionalRole): bool
    {
        return $user->can('professional-roles.delete');
    }
}
