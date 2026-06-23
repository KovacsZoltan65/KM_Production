<?php

namespace App\Policies;

use App\Enums\CustomerOrderStatus;
use App\Models\CustomerOrder;
use App\Models\User;

class CustomerOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customer-orders.view');
    }

    public function view(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('customer-orders.create');
    }

    public function update(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.update');
    }

    public function delete(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.delete')
            && in_array($customerOrder->status, [CustomerOrderStatus::Draft, CustomerOrderStatus::Cancelled], true);
    }

    public function confirm(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.confirm');
    }

    public function cancel(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.cancel');
    }
}
