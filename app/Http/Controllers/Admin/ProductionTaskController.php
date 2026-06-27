<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductionTaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinishProductionTaskRequest;
use App\Http\Requests\Admin\GenerateProductionTasksRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StartProductionTaskRequest;
use App\Http\Requests\Admin\StoreProductionTaskRequest;
use App\Http\Requests\Admin\UpdateProductionTaskRequest;
use App\Models\Employee;
use App\Models\ItemInstance;
use App\Models\OperationSequenceStep;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Services\Admin\ProductionTaskService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductionTaskController extends Controller
{
    public function __construct(private readonly ProductionTaskService $service) {}

    /**
     * @param IndexRequest $request
     * @return Response
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', ProductionTask::class);

        return Inertia::render('Admin/ProductionTasks/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'statusOptions' => $this->statusOptions(),
            'employeeOptions' => $this->employeeOptions(),
            'productionOrderOptions' => $this->productionOrderOptions(),
            'itemInstanceOptions' => $this->itemInstanceOptions(),
            'operationStepOptions' => $this->operationStepOptions(),
        ]);
    }

    /**
     * @param ProductionTask $productionTask
     * @return Response
     */
    public function show(ProductionTask $productionTask): Response
    {
        $this->authorize('view', $productionTask);

        return Inertia::render('Admin/ProductionTasks/Show', [
            'productionTask' => $this->service->findForShow($productionTask),
            'employeeOptions' => $this->employeeOptions(),
            'itemOptions' => $this->itemOptions(),
            'locationOptions' => $this->locationOptions(),
            'qualityResultOptions' => $this->qualityResultOptions(),
        ]);
    }

    /**
     * @param StoreProductionTaskRequest $request
     * @return RedirectResponse
     */
    public function store(StoreProductionTaskRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('admin.production-tasks.index')->with('success', 'Production task created.');
    }

    /**
     * @param UpdateProductionTaskRequest $request
     * @param ProductionTask $productionTask
     * @return RedirectResponse
     */
    public function update(UpdateProductionTaskRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->update($productionTask, $request->validated(), $request->user());

        return back()->with('success', 'Production task updated.');
    }

    /**
     * @param ProductionTask $productionTask
     * @return RedirectResponse
     */
    public function destroy(ProductionTask $productionTask): RedirectResponse
    {
        $this->authorize('delete', $productionTask);
        $this->service->delete($productionTask, request()->user());

        return redirect()->route('admin.production-tasks.index')->with('success', 'Production task deleted.');
    }

    /**
     * @param GenerateProductionTasksRequest $request
     * @return RedirectResponse
     */
    public function generateFromOrder(GenerateProductionTasksRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $count = $this->service->generateFromOrder(
            ProductionOrder::query()->findOrFail($payload['production_order_id']),
            Employee::query()->findOrFail($payload['employee_id']),
            $request->user(),
        );

        return back()->with('success', "{$count} production tasks generated.");
    }

    /**
     * @param StartProductionTaskRequest $request
     * @param ProductionTask $productionTask
     * @return RedirectResponse
     */
    public function start(StartProductionTaskRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->start($productionTask, $request->user());

        return back()->with('success', 'Production task started.');
    }

    /**
     * @param FinishProductionTaskRequest $request
     * @param ProductionTask $productionTask
     * @return RedirectResponse
     */
    public function finish(FinishProductionTaskRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->finish($productionTask, $request->user());

        return back()->with('success', 'Production task finished.');
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function statusOptions(): array
    {
        return collect(ProductionTaskStatus::cases())
            ->map(fn (ProductionTaskStatus $status): array => [
                'label' => str($status->value)->replace('_', ' ')->title()->toString(),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array[]
     */
    private function employeeOptions(): array
    {
        return Employee::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'employee_number', 'name'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'label' => "{$employee->employee_number} - {$employee->name}",
            ])
            ->all();
    }

    /**
     * @return array[]
     */
    private function productionOrderOptions(): array
    {
        return ProductionOrder::query()
            ->orderByDesc('created_at')
            ->get(['id', 'order_number'])
            ->map(fn (ProductionOrder $order): array => ['id' => $order->id, 'label' => $order->order_number])
            ->all();
    }

    /**
     * @return array[]
     */
    private function itemInstanceOptions(): array
    {
        return ItemInstance::query()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'serial_number'])
            ->map(fn (ItemInstance $instance): array => ['id' => $instance->id, 'label' => $instance->serial_number])
            ->all();
    }

    /**
     * @return array[]
     */
    private function operationStepOptions(): array
    {
        return OperationSequenceStep::query()
            ->with(['operationType', 'factoryUnit'])
            ->orderBy('operation_sequence_id')
            ->orderBy('step_order')
            ->get()
            ->map(fn (OperationSequenceStep $step): array => [
                'id' => $step->id,
                'label' => "#{$step->step_order} {$step->operationType?->name} ({$step->factoryUnit?->code})",
            ])
            ->all();
    }

    /**
     * @return array[]
     */
    private function itemOptions(): array
    {
        return \App\Models\Item::query()
            ->orderBy('item_number')
            ->get(['id', 'item_number', 'name', 'unit'])
            ->map(fn (\App\Models\Item $item): array => [
                'id' => $item->id,
                'unit' => $item->unit,
                'label' => "{$item->item_number} - {$item->name}",
            ])
            ->all();
    }

    /**
     * @return array[]
     */
    private function locationOptions(): array
    {
        return \App\Models\Location::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (\App\Models\Location $location): array => [
                'id' => $location->id,
                'label' => "{$location->code} - {$location->name}",
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function qualityResultOptions(): array
    {
        return collect(\App\Enums\QualityCheckResult::cases())
            ->map(fn (\App\Enums\QualityCheckResult $result): array => [
                'label' => str($result->value)->replace('_', ' ')->title()->toString(),
                'value' => $result->value,
            ])
            ->all();
    }
}
