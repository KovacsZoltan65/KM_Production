<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductionPlanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveProductionPlanRequest;
use App\Http\Requests\Admin\GenerateProductionOrdersRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreProductionPlanRequest;
use App\Http\Requests\Admin\UpdateProductionPlanRequest;
use App\Models\Bom;
use App\Models\CustomerOrder;
use App\Models\OperationSequence;
use App\Models\ProductionPlan;
use App\Services\Admin\ProductionPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ProductionPlanController extends Controller
{
    public function __construct(private readonly ProductionPlanService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', ProductionPlan::class);

        return Inertia::render('Admin/ProductionPlans/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'customerOrderOptions' => $this->customerOrderOptions(),
            'bomOptions' => $this->bomOptions(),
            'operationSequenceOptions' => $this->operationSequenceOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function show(ProductionPlan $productionPlan): Response
    {
        $this->authorize('view', $productionPlan);

        return Inertia::render('Admin/ProductionPlans/Show', [
            'productionPlan' => $this->service->findForShow($productionPlan),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StoreProductionPlanRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'Production plan created.');
    }

    public function update(UpdateProductionPlanRequest $request, ProductionPlan $productionPlan): RedirectResponse
    {
        $this->service->update($productionPlan, $request->validated(), $request->user());

        return back()->with('success', 'Production plan updated.');
    }

    public function destroy(ProductionPlan $productionPlan): RedirectResponse
    {
        $this->authorize('delete', $productionPlan);
        $this->service->delete($productionPlan, request()->user());

        return back()->with('success', 'Production plan deleted.');
    }

    public function approve(ApproveProductionPlanRequest $request, ProductionPlan $productionPlan): RedirectResponse
    {
        $this->service->approve($productionPlan, $request->user());

        return back()->with('success', 'Production plan approved.');
    }

    public function generateProductionOrders(GenerateProductionOrdersRequest $request, ProductionPlan $productionPlan): RedirectResponse
    {
        $this->service->generateProductionOrders($productionPlan, $request->user());

        return back()->with('success', 'Production orders generated.');
    }

    /**
     * @return Collection<int, array{id: int, order_number: string, customer_name: string, label: string}>
     */
    private function customerOrderOptions(): Collection
    {
        return CustomerOrder::query()
            ->with('customer')
            ->orderByDesc('created_at')
            ->get(['id', 'order_number', 'customer_id'])
            ->map(fn (CustomerOrder $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->name ?? '',
                'label' => "{$order->order_number} - ".($order->customer?->name ?? 'Unknown customer'),
            ]);
    }

    /**
     * @return Collection<int, array{id: int, item_id: int, label: string}>
     */
    private function bomOptions(): Collection
    {
        return Bom::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'item_id', 'version', 'name'])
            ->map(fn (Bom $bom): array => [
                'id' => $bom->id,
                'item_id' => $bom->item_id,
                'label' => "V{$bom->version} - {$bom->name}",
            ]);
    }

    /**
     * @return Collection<int, array{id: int, item_id: int, label: string}>
     */
    private function operationSequenceOptions(): Collection
    {
        return OperationSequence::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'item_id', 'version', 'name'])
            ->map(fn (OperationSequence $sequence): array => [
                'id' => $sequence->id,
                'item_id' => $sequence->item_id,
                'label' => "V{$sequence->version} - {$sequence->name}",
            ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function statusOptions(): array
    {
        return collect(ProductionPlanStatus::cases())
            ->map(fn (ProductionPlanStatus $status): array => [
                'label' => str($status->value)->replace('_', ' ')->title()->toString(),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }
}
