<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQualityCheckRequest;
use App\Models\ProductionTask;
use App\Services\Admin\QualityCheckService;
use Illuminate\Http\RedirectResponse;

class QualityCheckController extends Controller
{
    public function __construct(private readonly QualityCheckService $service) {}

    /**
     * @param StoreQualityCheckRequest $request
     * @param ProductionTask $productionTask
     * @return RedirectResponse
     */
    public function store(StoreQualityCheckRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->store($productionTask, $request->validated(), $request->user());

        return back()->with('success', 'Quality check recorded.');
    }
}
