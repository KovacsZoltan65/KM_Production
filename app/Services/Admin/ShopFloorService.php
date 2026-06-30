<?php

namespace App\Services\Admin;

use App\Models\Employee;
use App\Models\ProductionTask;
use App\Models\User;
use App\Repositories\Contracts\ProductionTaskRepositoryInterface;
use Illuminate\Support\Collection;

class ShopFloorService
{
    public function __construct(private readonly ProductionTaskRepositoryInterface $productionTasks) {}

    /**
     * @return Collection<int, ProductionTask>
     */
    public function tasks(): Collection
    {
        return $this->productionTasks->readyAndActiveForShopFloor();
    }

    /**
     * @return Collection<int, ProductionTask>
     */
    public function myTasks(User $user): Collection
    {
        $employee = Employee::query()->where('user_id', $user->id)->first();

        return $this->productionTasks->readyAndActiveForShopFloor($employee?->id);
    }
}
