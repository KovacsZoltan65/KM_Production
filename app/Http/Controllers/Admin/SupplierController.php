<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreSupplierRequest;
use App\Http\Requests\Admin\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\Admin\SupplierAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierAdminService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', Supplier::class);

        return Inertia::render('Admin/Suppliers/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('messages.created'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->service->update($supplier, $request->validated(), $request->user());

        return back()->with('success', __('messages.updated'));
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);
        $this->service->delete($supplier, request()->user());

        return back()->with('success', __('messages.deleted'));
    }
}
