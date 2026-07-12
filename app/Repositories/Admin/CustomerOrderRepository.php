<?php

namespace App\Repositories\Admin;

use App\Enums\CustomerOrderStatus;
use App\Models\CustomerOrder;
use App\Repositories\Contracts\CustomerOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** A vevői rendelések szűrését és tételsorokkal együtt történő perzisztenciáját kezeli. */
class CustomerOrderRepository extends AbstractAdminRepository implements CustomerOrderRepositoryInterface
{
    protected string $modelClass = CustomerOrder::class;

    protected array $sortable = ['id', 'order_number', 'status', 'requested_delivery_date', 'created_at'];

    protected array $with = ['customer', 'items.item'];

    /**
     * A rendelésszámban és vevőadatokban kereshető, lapozott listát ad vissza.
     *
     * @param  array{search?: string|null, status?: string|null, sort?: string|null,
     *     direction?: string|null}  $filters  Az alkalmazandó szűrők.
     * @return LengthAwarePaginator<int, Model> A lapozott rendelések.
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query()->withCount('items');
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($search): void {
                        $customerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $sort = \in_array($filters['sort'] ?? null, $this->sortable, true)
            ? (string) $filters['sort']
            : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createWithItems(array $attributes, array $items): CustomerOrder
    {
        return DB::transaction(function () use ($attributes, $items): CustomerOrder {
            $attributes['order_number'] = $this->nextOrderNumber();
            $attributes['status'] = CustomerOrderStatus::Draft->value;

            /** @var CustomerOrder $customerOrder */
            $customerOrder = $this->query()->create($attributes);
            $customerOrder->items()->createMany($items);

            return $customerOrder->load(['customer', 'items.item'])->loadCount('items');
        });
    }

    public function updateWithItems(CustomerOrder $customerOrder, array $attributes, array $items): CustomerOrder
    {
        return DB::transaction(function () use ($customerOrder, $attributes, $items): CustomerOrder {
            $customerOrder->update($attributes);
            $customerOrder->items()->delete();
            $customerOrder->items()->createMany($items);

            return $customerOrder->refresh()->load(['customer', 'items.item'])->loadCount('items');
        });
    }

    public function findForShow(CustomerOrder $customerOrder): CustomerOrder
    {
        return $customerOrder->load(['customer', 'items.item', 'productionPlans'])->loadCount('items');
    }

    public function confirm(CustomerOrder $customerOrder): CustomerOrder
    {
        $customerOrder->update([
            'status' => CustomerOrderStatus::Confirmed->value,
            'confirmed_at' => now(),
        ]);

        return $customerOrder->refresh()->load(['customer', 'items.item'])->loadCount('items');
    }

    public function cancel(CustomerOrder $customerOrder): CustomerOrder
    {
        $customerOrder->update([
            'status' => CustomerOrderStatus::Cancelled->value,
        ]);

        return $customerOrder->refresh()->load(['customer', 'items.item'])->loadCount('items');
    }

    /**
     * Sorzárolás mellett előállítja a következő éves vevőirendelés-számot.
     */
    private function nextOrderNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "SO-{$year}-";
        $lastOrderNumber = CustomerOrder::query()
            ->withTrashed()
            ->where('order_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('order_number')
            ->value('order_number');

        $next = 1;

        if (is_string($lastOrderNumber)) {
            $next = ((int) substr($lastOrderNumber, \strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
