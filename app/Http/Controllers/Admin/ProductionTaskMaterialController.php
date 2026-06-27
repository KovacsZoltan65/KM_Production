<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductionTaskMaterialRequest;
use App\Models\ProductionTask;
use App\Services\Admin\ProductionTaskMaterialService;
use Illuminate\Http\RedirectResponse;

class ProductionTaskMaterialController extends Controller
{
    public function __construct(private readonly ProductionTaskMaterialService $service) {}

    /**
     * @param StoreProductionTaskMaterialRequest $request
     * @param ProductionTask $productionTask
     * @return RedirectResponse
     */
    public function store(StoreProductionTaskMaterialRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->store($productionTask, $request->validated(), $request->user());

        return back()->with('success', 'Material usage recorded.');
    }
}
