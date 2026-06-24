<?php

namespace App\Services\Admin;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\ProductionTaskRepositoryInterface;
use Illuminate\Support\Collection;

class ShopFloorService
{
    public function __construct(private readonly ProductionTaskRepositoryInterface $productionTasks) {}

    /**
     * @return Collection<int, \App\Models\ProductionTask>
     */
    public function tasks(): Collection
    {
        return $this->productionTasks->readyAndActiveForShopFloor();
    }

    /**
     * @return Collection<int, \App\Models\ProductionTask>
     */
    public function myTasks(User $user): Collection
    {
        $employee = Employee::query()->where('user_id', $user->id)->first();

        return $this->productionTasks->readyAndActiveForShopFloor($employee?->id);
    }
}
