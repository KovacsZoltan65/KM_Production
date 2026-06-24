<?php

namespace App\Repositories\Admin;

use App\Enums\ProductionTaskStatus;
use App\Models\ProductionTask;
use App\Repositories\Contracts\ProductionTaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductionTaskRepository extends AbstractAdminRepository implements ProductionTaskRepositoryInterface
{
    protected string $modelClass = ProductionTask::class;

    protected array $sortable = ['id', 'status', 'started_at', 'finished_at', 'created_at'];

    protected array $with = [
        'productionOrder.item',
        'itemInstance',
        'operationSequenceStep.operationType',
        'operationSequenceStep.factoryUnit',
        'employee',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForExecution(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->executionQuery();
        $this->applyFilters($query, $filters);

        $sort = in_array($filters['sort'] ?? null, $this->sortable, true) ? (string) $filters['sort'] : 'id';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function findForShow(ProductionTask $productionTask): ProductionTask
    {
        return $productionTask->load([
            ...$this->with,
            'materials.item',
            'materials.itemBatch',
            'qualityChecks.inspector',
        ]);
    }

    /**
     * @return Collection<int, ProductionTask>
     */
    public function readyAndActiveForShopFloor(?int $employeeId = null): Collection
    {
        return $this->executionQuery()
            ->whereIn('status', [
                ProductionTaskStatus::Ready->value,
                ProductionTaskStatus::InProgress->value,
                ProductionTaskStatus::WaitingForCheck->value,
            ])
            ->when($employeeId !== null, fn (Builder $query) => $query->where('employee_id', $employeeId))
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'ready' THEN 2 WHEN 'waiting_for_check' THEN 3 ELSE 4 END")
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Builder<ProductionTask>
     */
    private function executionQuery(): Builder
    {
        return ProductionTask::query()->with($this->with);
    }

    /**
     * @param  Builder<ProductionTask>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->whereHas('productionOrder', fn (Builder $orderQuery) => $orderQuery->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('itemInstance', fn (Builder $instanceQuery) => $instanceQuery->where('serial_number', 'like', "%{$search}%"))
                    ->orWhereHas('employee', fn (Builder $employeeQuery) => $employeeQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['production_order_id'])) {
            $query->where('production_order_id', $filters['production_order_id']);
        }
    }
}
