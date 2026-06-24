<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Models\StockBalance;
use App\Services\Admin\InventoryQueryService;
use Inertia\Inertia;
use Inertia\Response;

class StockBalanceController extends Controller
{
    public function __construct(private readonly InventoryQueryService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', StockBalance::class);

        return Inertia::render('Admin/Inventory/StockBalances/Index', [
            'records' => $this->service->paginateStockBalances($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
        ]);
    }
}
