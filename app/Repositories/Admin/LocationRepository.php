<?php

namespace App\Repositories\Admin;

use App\Models\Location;
use App\Repositories\Contracts\LocationRepositoryInterface;

class LocationRepository extends AbstractAdminRepository implements LocationRepositoryInterface
{
    protected string $modelClass = Location::class;

    protected array $searchable = ['code', 'name', 'description'];

    protected array $sortable = ['id', 'code', 'name', 'location_type', 'is_active', 'created_at'];

    protected array $with = ['factoryUnit'];
}
