<?php

namespace App\Policies;

use App\Models\QualityCheck;
use App\Models\User;

class QualityCheckPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('production-tasks.check');
    }

    public function view(User $user, QualityCheck $qualityCheck): bool
    {
        return $user->can('production-tasks.check');
    }

    public function create(User $user): bool
    {
        return $user->can('production-tasks.check');
    }
}
