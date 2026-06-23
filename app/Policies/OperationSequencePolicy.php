<?php

namespace App\Policies;

use App\Models\OperationSequence;
use App\Models\User;

class OperationSequencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operation-sequences.view');
    }

    public function create(User $user): bool
    {
        return $user->can('operation-sequences.create');
    }

    public function update(User $user, OperationSequence $operationSequence): bool
    {
        return $user->can('operation-sequences.update');
    }

    public function delete(User $user, OperationSequence $operationSequence): bool
    {
        return $user->can('operation-sequences.delete');
    }
}
