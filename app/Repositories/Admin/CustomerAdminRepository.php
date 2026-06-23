<?php

namespace App\Repositories\Admin;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerAdminRepositoryInterface;

class CustomerAdminRepository extends AbstractAdminRepository implements CustomerAdminRepositoryInterface
{
    protected string $modelClass = Customer::class;

    protected array $searchable = ['code', 'name', 'tax_number', 'email', 'phone'];

    protected array $sortable = ['id', 'code', 'name', 'tax_number', 'email', 'phone', 'is_active', 'created_at'];
}
