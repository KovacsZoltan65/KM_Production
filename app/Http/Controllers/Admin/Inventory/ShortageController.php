<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Models\MaterialRequirement;
use App\Services\Admin\InventoryQueryService;
use Inertia\Inertia;
use Inertia\Response;

class ShortageController extends Controller
{
    public function __construct(private readonly InventoryQueryService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', MaterialRequirement::class);

        return Inertia::render('Admin/Inventory/Shortages/Index', [
            'records' => $this->service->paginateShortages($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
        ]);
    }
}
