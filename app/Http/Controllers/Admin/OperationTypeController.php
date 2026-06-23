<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OperationTypeCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreOperationTypeRequest;
use App\Http\Requests\Admin\UpdateOperationTypeRequest;
use App\Models\OperationType;
use App\Services\Admin\OperationTypeAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OperationTypeController extends Controller
{
    public function __construct(private readonly OperationTypeAdminService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', OperationType::class);

        return Inertia::render('Admin/OperationTypes/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'operationTypeCodes' => collect(OperationTypeCode::cases())
                ->map(fn (OperationTypeCode $code): array => [
                    'label' => str($code->value)->replace('_', ' ')->title()->toString(),
                    'value' => $code->value,
                ])
                ->values(),
        ]);
    }

    public function store(StoreOperationTypeRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'Operation type created.');
    }

    public function update(UpdateOperationTypeRequest $request, OperationType $operationType): RedirectResponse
    {
        $this->service->update($operationType, $request->validated(), $request->user());

        return back()->with('success', 'Operation type updated.');
    }

    public function destroy(OperationType $operationType): RedirectResponse
    {
        $this->authorize('delete', $operationType);
        $this->service->delete($operationType, request()->user());

        return back()->with('success', 'Operation type deleted.');
    }
}
