<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductionTaskStatus;
use App\Enums\QualityCheckResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinishProductionTaskRequest;
use App\Http\Requests\Admin\GenerateProductionTasksRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StartProductionTaskRequest;
use App\Http\Requests\Admin\StoreProductionTaskRequest;
use App\Http\Requests\Admin\UpdateProductionTaskRequest;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\Location;
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

    public function store(StoreProductionTaskRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('admin.production-tasks.index')->with('success', __('production.tasks.messages.created'));
    }

    public function update(UpdateProductionTaskRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->update($productionTask, $request->validated(), $request->user());

        return back()->with('success', __('production.tasks.messages.updated'));
    }

    public function destroy(ProductionTask $productionTask): RedirectResponse
    {
        $this->authorize('delete', $productionTask);
        $this->service->delete($productionTask, request()->user());

        return redirect()->route('admin.production-tasks.index')->with('success', __('production.tasks.messages.deleted'));
    }

    public function generateFromOrder(GenerateProductionTasksRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $count = $this->service->generateFromOrder(
            ProductionOrder::query()->findOrFail($payload['production_order_id']),
            Employee::query()->findOrFail($payload['employee_id']),
            $request->user(),
        );

        return back()->with('success', __('production.tasks.messages.generated', ['count' => $count]));
    }

    public function start(StartProductionTaskRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->start($productionTask, $request->user());

        return back()->with('success', __('production.tasks.messages.started'));
    }

    public function finish(FinishProductionTaskRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->finish($productionTask, $request->user());

        return back()->with('success', __('production.tasks.messages.finished'));
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function statusOptions(): array
    {
        return collect(ProductionTaskStatus::cases())
            ->map(fn (ProductionTaskStatus $status): array => [
                'label' => __("status.{$status->value}"),
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
        return Item::query()
            ->orderBy('item_number')
            ->get(['id', 'item_number', 'name', 'unit'])
            ->map(fn (Item $item): array => [
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
        return Location::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (Location $location): array => [
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
        return collect(QualityCheckResult::cases())
            ->map(fn (QualityCheckResult $result): array => [
                'label' => __("enum.quality_check_result.{$result->value}"),
                'value' => $result->value,
            ])
            ->all();
    }
}
