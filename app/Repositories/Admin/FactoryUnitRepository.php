<?php

namespace App\Repositories\Admin;

use App\Models\FactoryUnit;
use App\Repositories\Contracts\FactoryUnitRepositoryInterface;

class FactoryUnitRepository extends AbstractAdminRepository implements FactoryUnitRepositoryInterface
{
    protected string $modelClass = FactoryUnit::class;

    protected array $searchable = ['code', 'name', 'description'];

    protected array $sortable = ['id', 'code', 'name', 'daily_capacity_minutes', 'shift_count', 'is_active', 'created_at'];
}
