<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreOperationSequenceRequest;
use App\Http\Requests\Admin\UpdateOperationSequenceRequest;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\OperationSequence;
use App\Models\OperationType;
use App\Models\ProfessionalRole;
use App\Services\Admin\OperationSequenceAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OperationSequenceController extends Controller
{
    public function __construct(private readonly OperationSequenceAdminService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', OperationSequence::class);

        return Inertia::render('Admin/OperationSequences/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'itemOptions' => Item::query()
                ->where('is_active', '=', true)
                ->orderBy('item_number')
                ->get(['id', 'item_number', 'name', 'unit']),
            'operationTypeOptions' => OperationType::query()
                ->where('is_active', '=', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'factoryUnitOptions' => FactoryUnit::query()
                ->where('is_active', '=', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'professionalRoleOptions' => ProfessionalRole::query()
                ->where('is_active', '=', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreOperationSequenceRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'Operation sequence created.');
    }

    public function update(UpdateOperationSequenceRequest $request, OperationSequence $operationSequence): RedirectResponse
    {
        $this->service->update($operationSequence, $request->validated(), $request->user());

        return back()->with('success', 'Operation sequence updated.');
    }

    public function destroy(OperationSequence $operationSequence): RedirectResponse
    {
        $this->authorize('delete', $operationSequence);
        $this->service->delete($operationSequence, request()->user());

        return back()->with('success', 'Operation sequence deleted.');
    }
}
