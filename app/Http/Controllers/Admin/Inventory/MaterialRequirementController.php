<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Enums\MaterialRequirementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Models\CustomerOrder;
use App\Models\Item;
use App\Models\MaterialRequirement;
use App\Services\Admin\InventoryQueryService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MaterialRequirementController extends Controller
{
    public function __construct(private readonly InventoryQueryService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', MaterialRequirement::class);

        return Inertia::render('Admin/Inventory/MaterialRequirements/Index', [
            'records' => $this->service->paginateMaterialRequirements($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'statusOptions' => $this->statusOptions(),
            'itemOptions' => $this->itemOptions(),
            'customerOrderOptions' => $this->customerOrderOptions(),
        ]);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function statusOptions(): array
    {
        return collect(MaterialRequirementStatus::cases())
            ->map(fn (MaterialRequirementStatus $status): array => [
                'label' => str($status->value)->replace('_', ' ')->title()->toString(),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{id: int, label: non-falsy-string}>
     */
    private function itemOptions(): Collection
    {
        return Item::query()
            ->orderBy('item_number')
            ->get(['id', 'item_number', 'name'])
            ->map(fn (Item $item): array => [
                'id' => $item->id,
                'label' => "{$item->item_number} - {$item->name}",
            ]);
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function customerOrderOptions(): Collection
    {
        return CustomerOrder::query()
            ->orderByDesc('created_at')
            ->get(['id', 'order_number'])
            ->map(fn (CustomerOrder $order): array => [
                'id' => $order->id,
                'label' => $order->order_number,
            ]);
    }
}
