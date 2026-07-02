<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApprovePurchaseOrderRequest;
use App\Http\Requests\Admin\ClosePurchaseOrderRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StorePurchaseOrderRequest;
use App\Http\Requests\Admin\UpdatePurchaseOrderRequest;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Admin\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        return Inertia::render('Admin/PurchaseOrders/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'statusOptions' => $this->statusOptions(),
            'supplierOptions' => $this->supplierOptions(),
            'itemOptions' => $this->itemOptions(),
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder);

        return Inertia::render('Admin/PurchaseOrders/Show', [
            'purchaseOrder' => $this->service->findForShow($purchaseOrder),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('procurement.purchase_orders.messages.created'));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->update($purchaseOrder, $request->validated(), $request->user());

        return back()->with('success', __('procurement.purchase_orders.messages.updated'));
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('delete', $purchaseOrder);
        $this->service->delete($purchaseOrder, request()->user());

        return back()->with('success', __('procurement.purchase_orders.messages.deleted'));
    }

    public function approve(ApprovePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->approve($purchaseOrder, $request->user());

        return back()->with('success', __('procurement.purchase_orders.messages.approved'));
    }

    public function close(ClosePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->close($purchaseOrder, $request->user());

        return back()->with('success', __('procurement.purchase_orders.messages.closed'));
    }

    /**
     * @return array[]
     */
    private function statusOptions(): array
    {
        return collect(PurchaseOrderStatus::cases())
            ->map(fn (PurchaseOrderStatus $status): array => [
                'label' => __("status.{$status->value}"),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array>
     */
    private function supplierOptions(): Collection
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'label' => "{$supplier->code} - {$supplier->name}",
            ]);
    }

    /**
     * @return Collection<int, array>
     */
    private function itemOptions(): Collection
    {
        return Item::query()
            ->orderBy('item_number')
            ->get(['id', 'item_number', 'name', 'unit'])
            ->map(fn (Item $item): array => [
                'id' => $item->id,
                'unit' => $item->unit,
                'label' => "{$item->item_number} - {$item->name}",
            ]);
    }
}
