<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Enums\StockReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\ReleaseStockReservationRequest;
use App\Models\StockReservation;
use App\Services\Admin\InventoryQueryService;
use App\Services\Admin\StockReservationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockReservationController extends Controller
{
    public function __construct(
        private readonly InventoryQueryService $queryService,
        private readonly StockReservationService $reservationService,
    ) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', StockReservation::class);

        return Inertia::render('Admin/Inventory/StockReservations/Index', [
            'records' => $this->queryService->paginateStockReservations($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function release(ReleaseStockReservationRequest $request, StockReservation $stockReservation): RedirectResponse
    {
        $this->reservationService->release($stockReservation, $request->user());

        return back()->with('success', __('inventory.stock_reservations.released'));
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function statusOptions(): array
    {
        return collect(StockReservationStatus::cases())
            ->map(fn (StockReservationStatus $status): array => [
                'label' => str($status->value)->replace('_', ' ')->title()->toString(),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }
}
