<?php

namespace App\Repositories\Admin;

use App\Enums\ProductionOrderStatus;
use App\Enums\ProductionPlanStatus;
use App\Models\ProductionOrder;
use App\Models\ProductionPlan;
use App\Repositories\Contracts\ProductionPlanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionPlanRepository extends AbstractAdminRepository implements ProductionPlanRepositoryInterface
{
    protected string $modelClass = ProductionPlan::class;

    protected array $sortable = ['id', 'plan_number', 'status', 'planned_start_date', 'planned_finish_date', 'created_at'];

    protected array $with = ['customerOrder.customer', 'items.item', 'items.bom', 'items.operationSequence'];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query()->withCount('items');
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('plan_number', 'like', "%{$search}%")
                    ->orWhereHas('customerOrder', function (Builder $orderQuery) use ($search): void {
                        $orderQuery->where('order_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customerOrder.customer', function (Builder $customerQuery) use ($search): void {
                        $customerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $sort = in_array($filters['sort'] ?? null, $this->sortable, true)
            ? (string) $filters['sort']
            : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createWithItems(array $attributes, array $items): ProductionPlan
    {
        return DB::transaction(function () use ($attributes, $items): ProductionPlan {
            $attributes['plan_number'] = $this->nextPlanNumber();
            $attributes['status'] = ProductionPlanStatus::Draft->value;

            /** @var ProductionPlan $productionPlan */
            $productionPlan = $this->query()->create($attributes);
            $productionPlan->items()->createMany($items);

            return $this->findForShow($productionPlan);
        });
    }

    public function updateWithItems(ProductionPlan $productionPlan, array $attributes, array $items): ProductionPlan
    {
        return DB::transaction(function () use ($productionPlan, $attributes, $items): ProductionPlan {
            $productionPlan->update($attributes);

            foreach ($items as $item) {
                $itemId = $item['id'] ?? null;
                unset($item['id']);

                $productionPlan->items()
                    ->whereKey($itemId)
                    ->update($item);
            }

            return $this->findForShow($productionPlan->refresh());
        });
    }

    public function findForShow(ProductionPlan $productionPlan): ProductionPlan
    {
        return $productionPlan
            ->load([
                'customerOrder.customer',
                'items.item',
                'items.bom',
                'items.operationSequence',
                'items.productionOrders.item',
            ])
            ->loadCount('items');
    }

    public function approve(ProductionPlan $productionPlan, ?int $approvedBy = null): ProductionPlan
    {
        $productionPlan->update([
            'status' => ProductionPlanStatus::Approved->value,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $this->findForShow($productionPlan->refresh());
    }

    public function generateProductionOrders(ProductionPlan $productionPlan, ?int $createdBy = null): Collection
    {
        return DB::transaction(function () use ($productionPlan, $createdBy): Collection {
            $createdOrders = collect();
            $productionPlan = $this->findForShow($productionPlan);

            foreach ($productionPlan->items as $item) {
                if ($item->productionOrders->isNotEmpty()) {
                    continue;
                }

                $createdOrders->push(ProductionOrder::query()->create([
                    'production_plan_item_id' => $item->id,
                    'customer_order_item_id' => $item->customer_order_item_id,
                    'item_id' => $item->item_id,
                    'bom_id' => $item->bom_id,
                    'operation_sequence_id' => $item->operation_sequence_id,
                    'order_number' => $this->nextProductionOrderNumber(),
                    'quantity' => $item->quantity,
                    'status' => ProductionOrderStatus::Planned->value,
                    'planned_start_date' => $item->planned_start_date,
                    'planned_finish_date' => $item->planned_finish_date,
                    'created_by' => $createdBy,
                ]));
            }

            return $createdOrders;
        });
    }

    private function nextPlanNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "PP-{$year}-";
        $lastPlanNumber = ProductionPlan::query()
            ->withTrashed()
            ->where('plan_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('plan_number')
            ->value('plan_number');

        $next = is_string($lastPlanNumber)
            ? ((int) substr($lastPlanNumber, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function nextProductionOrderNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "PO-{$year}-";
        $lastOrderNumber = ProductionOrder::query()
            ->withTrashed()
            ->where('order_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('order_number')
            ->value('order_number');

        $next = is_string($lastOrderNumber)
            ? ((int) substr($lastOrderNumber, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
