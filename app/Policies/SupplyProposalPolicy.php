<?php

namespace App\Policies;

use App\Models\SupplyProposal;
use App\Models\User;

class SupplyProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supply-proposals.view');
    }

    public function view(User $user, SupplyProposal $proposal): bool
    {
        return $user->can('supply-proposals.view');
    }

    public function create(User $user): bool
    {
        return $user->can('supply-proposals.create');
    }

    public function update(User $user, SupplyProposal $proposal): bool
    {
        return $user->can('supply-proposals.update');
    }

    public function propose(User $user, SupplyProposal $proposal): bool
    {
        return $user->can('supply-proposals.update');
    }

    public function approve(User $user, SupplyProposal $proposal): bool
    {
        return $user->can('supply-proposals.approve');
    }

    public function reject(User $user, SupplyProposal $proposal): bool
    {
        return $user->can('supply-proposals.approve');
    }

    public function cancel(User $user, SupplyProposal $proposal): bool
    {
        return $user->can('supply-proposals.delete');
    }
}
