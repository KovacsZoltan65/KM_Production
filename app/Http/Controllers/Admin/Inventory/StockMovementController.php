<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockMovement;
use App\Services\Admin\InventoryQueryService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function __construct(private readonly InventoryQueryService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', StockMovement::class);

        return Inertia::render('Admin/Inventory/StockMovements/Index', [
            'records' => $this->service->paginateStockMovements($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'movementTypeOptions' => $this->movementTypeOptions(),
            'itemOptions' => $this->itemOptions(),
            'locationOptions' => $this->locationOptions(),
        ]);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function movementTypeOptions(): array
    {
        return collect(StockMovementType::cases())
            ->map(fn (StockMovementType $type): array => [
                'label' => str($type->value)->replace('_', ' ')->title()->toString(),
                'value' => $type->value,
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
     * @return Collection<int, array{id: int, label: non-falsy-string}>
     */
    private function locationOptions(): Collection
    {
        return Location::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (Location $location): array => [
                'id' => $location->id,
                'label' => "{$location->code} - {$location->name}",
            ]);
    }
}
