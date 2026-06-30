<?php

namespace App\Repositories\Contracts;

use App\Models\ProductionOrder;
use App\Models\ProductionPlan;
use Illuminate\Support\Collection;

interface ProductionPlanRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createWithItems(array $attributes, array $items): ProductionPlan;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function updateWithItems(ProductionPlan $productionPlan, array $attributes, array $items): ProductionPlan;

    public function findForShow(ProductionPlan $productionPlan): ProductionPlan;

    /**
     * @return Collection<int, ProductionOrder>
     */
    public function generateProductionOrders(ProductionPlan $productionPlan, ?int $createdBy = null): Collection;

    public function approve(ProductionPlan $productionPlan, ?int $approvedBy = null): ProductionPlan;
}
