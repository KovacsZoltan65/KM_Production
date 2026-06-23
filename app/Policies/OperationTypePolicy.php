<?php

namespace App\Policies;

use App\Models\OperationType;
use App\Models\User;

class OperationTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operation-types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('operation-types.create');
    }

    public function update(User $user, OperationType $operationType): bool
    {
        return $user->can('operation-types.update');
    }

    public function delete(User $user, OperationType $operationType): bool
    {
        return $user->can('operation-types.delete');
    }
}
