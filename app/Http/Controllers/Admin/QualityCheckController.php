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

    public function store(StoreQualityCheckRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->store($productionTask, $request->validated(), $request->user());

        return back()->with('success', __('quality.messages.recorded'));
    }
}
