<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseRequisitionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApprovePurchaseRequisitionRequest;
use App\Http\Requests\Admin\GeneratePurchaseOrderRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StorePurchaseRequisitionRequest;
use App\Http\Requests\Admin\UpdatePurchaseRequisitionRequest;
use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Services\Admin\PurchaseRequisitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRequisitionController extends Controller
{
    public function __construct(private readonly PurchaseRequisitionService $service) {}

    /**
     * @param IndexRequest $request
     * @return Response
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', PurchaseRequisition::class);

        return Inertia::render('Admin/PurchaseRequisitions/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'statusOptions' => $this->statusOptions(),
            'itemOptions' => $this->itemOptions(),
        ]);
    }

    /**
     * Summary of show
     * @param PurchaseRequisition $purchaseRequisition
     * @return Response
     */
    public function show(PurchaseRequisition $purchaseRequisition): Response
    {
        $this->authorize('view', $purchaseRequisition);

        return Inertia::render('Admin/PurchaseRequisitions/Show', [
            'purchaseRequisition' => $this->service->findForShow($purchaseRequisition),
            'supplierOptions' => $this->supplierOptions(),
        ]);
    }

    /**
     * @param StorePurchaseRequisitionRequest $request
     * @return RedirectResponse
     */
    public function store(StorePurchaseRequisitionRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'Purchase requisition created.');
    }

    /**
     * @param UpdatePurchaseRequisitionRequest $request
     * @param PurchaseRequisition $purchaseRequisition
     * @return RedirectResponse
     */
    public function update(UpdatePurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->service->update($purchaseRequisition, $request->validated(), $request->user());

        return back()->with('success', 'Purchase requisition updated.');
    }

    /**
     * @param PurchaseRequisition $purchaseRequisition
     * @return RedirectResponse
     */
    public function destroy(PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->authorize('delete', $purchaseRequisition);
        $this->service->delete($purchaseRequisition, request()->user());

        return back()->with('success', 'Purchase requisition deleted.');
    }

    /**
     * @param ApprovePurchaseRequisitionRequest $request
     * @param PurchaseRequisition $purchaseRequisition
     * @return RedirectResponse
     */
    public function approve(ApprovePurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->service->approve($purchaseRequisition, $request->user());

        return back()->with('success', 'Purchase requisition approved.');
    }

    /**
     * @param GeneratePurchaseOrderRequest $request
     * @param PurchaseRequisition $purchaseRequisition
     * @return RedirectResponse
     */
    public function generatePurchaseOrder(GeneratePurchaseOrderRequest $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $purchaseOrder = $this->service->generatePurchaseOrder(
            $purchaseRequisition,
            (int) $request->validated('supplier_id'),
            $request->validated('expected_delivery_date'),
            $request->user()
        );

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order generated.');
    }

    /**
     * @param IndexRequest $request
     * @return RedirectResponse
     */
    public function generateFromMaterialRequirements(IndexRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseRequisition::class);
        $requisition = $this->service->generateFromMaterialRequirements($request->user());

        return redirect()->route('admin.purchase-requisitions.show', $requisition)->with('success', 'Purchase requisition generated.');
    }

    /**
     * @return array[]
     */
    private function statusOptions(): array
    {
        return collect(PurchaseRequisitionStatus::cases())
            ->map(fn (PurchaseRequisitionStatus $status): array => [
                'label' => str($status->value)->replace('_', ' ')->title()->toString(),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection
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

    /**
     * @return Collection
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
}
