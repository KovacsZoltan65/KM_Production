<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreItemSupplierRequest;
use App\Http\Requests\Admin\UpdateItemSupplierRequest;
use App\Models\ItemSupplier;
use App\Services\Admin\ItemSupplierService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ItemSupplierController extends Controller
{
    public function __construct(private readonly ItemSupplierService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', ItemSupplier::class);

        return Inertia::render('Admin/ItemSuppliers/Index', [
            'records' => fn () => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'itemOptions' => fn () => $this->service->itemOptions(),
            'supplierOptions' => fn () => $this->service->supplierOptions(),
        ]);
    }

    public function store(StoreItemSupplierRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('procurement.sources.messages.created'));
    }

    public function update(UpdateItemSupplierRequest $request, ItemSupplier $itemSupplier): RedirectResponse
    {
        $this->service->update($itemSupplier, $request->validated(), $request->user());

        return back()->with('success', __('procurement.sources.messages.updated'));
    }

    public function destroy(ItemSupplier $itemSupplier): RedirectResponse
    {
        $this->authorize('delete', $itemSupplier);
        $this->service->deactivate($itemSupplier, request()->user());

        return back()->with('success', __('procurement.sources.messages.inactivated'));
    }
}
