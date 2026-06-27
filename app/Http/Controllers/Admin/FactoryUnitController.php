<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreFactoryUnitRequest;
use App\Http\Requests\Admin\UpdateFactoryUnitRequest;
use App\Models\FactoryUnit;
use App\Services\Admin\FactoryUnitAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FactoryUnitController extends Controller
{
    public function __construct(private readonly FactoryUnitAdminService $service) {}

    /**
     * @param IndexRequest $request
     * @return Response
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', FactoryUnit::class);

        return Inertia::render('Admin/FactoryUnits/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
        ]);
    }

    /**
     * @param StoreFactoryUnitRequest $request
     * @return RedirectResponse
     */
    public function store(StoreFactoryUnitRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'Factory unit created.');
    }

    /**
     * @param UpdateFactoryUnitRequest $request
     * @param FactoryUnit $factoryUnit
     * @return RedirectResponse
     */
    public function update(UpdateFactoryUnitRequest $request, FactoryUnit $factoryUnit): RedirectResponse
    {
        $this->service->update($factoryUnit, $request->validated(), $request->user());

        return back()->with('success', 'Factory unit updated.');
    }

    /**
     * @param FactoryUnit $factoryUnit
     * @return RedirectResponse
     */
    public function destroy(FactoryUnit $factoryUnit): RedirectResponse
    {
        $this->authorize('delete', $factoryUnit);
        $this->service->delete($factoryUnit, request()->user());

        return back()->with('success', 'Factory unit deleted.');
    }
}
