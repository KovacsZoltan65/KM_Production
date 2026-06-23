<?php

namespace App\Repositories\Admin;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierAdminRepositoryInterface;

class SupplierAdminRepository extends AbstractAdminRepository implements SupplierAdminRepositoryInterface
{
    protected string $modelClass = Supplier::class;

    protected array $searchable = ['code', 'name', 'tax_number', 'email', 'phone'];

    protected array $sortable = ['id', 'code', 'name', 'tax_number', 'email', 'phone', 'is_active', 'created_at'];
}
