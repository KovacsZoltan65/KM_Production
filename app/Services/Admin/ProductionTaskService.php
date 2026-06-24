<?php

namespace App\Services\Admin;

use App\Enums\ItemInstanceStatus;
use App\Enums\ProductionOrderStatus;
use App\Enums\ProductionTaskStatus;
use App\Models\Employee;
use App\Models\ItemInstance;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Models\SerialSequence;
use App\Models\User;
use App\Repositories\Contracts\ProductionTaskRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionTaskService
{
    public function __construct(
        private readonly ProductionTaskRepositoryInterface $productionTasks,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->productionTasks->paginateForExecution($filters, $perPage);
    }

    public function findForShow(ProductionTask $productionTask): ProductionTask
    {
        return $this->productionTasks->findForShow($productionTask);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?User $causer = null): ProductionTask
    {
        $task = ProductionTask::query()->create([
            ...$attributes,
            'status' => $attributes['status'] ?? ProductionTaskStatus::Planned->value,
        ]);

        $this->auditLogService->log('production_task_created', $task, [], $causer);

        return $this->productionTasks->findForShow($task);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ProductionTask $productionTask, array $attributes, ?User $causer = null): ProductionTask
    {
        $productionTask->update($attributes);
        $this->auditLogService->log('production_task_updated', $productionTask, [], $causer);

        return $this->productionTasks->findForShow($productionTask);
    }

    public function delete(ProductionTask $productionTask, ?User $causer = null): void
    {
        $this->auditLogService->log('production_task_deleted', $productionTask, [], $causer);
        $productionTask->delete();
    }

    public function generateFromOrder(ProductionOrder $productionOrder, Employee $employee, ?User $causer = null): int
    {
        return DB::transaction(function () use ($productionOrder, $employee, $causer): int {
            $productionOrder->load('operationSequence.steps.factoryUnit');

            if ($productionOrder->operationSequence->steps->isEmpty()) {
                throw ValidationException::withMessages(['production_order_id' => 'The production order has no operation sequence steps.']);
            }

            if (ProductionTask::query()->where('production_order_id', $productionOrder->id)->exists()) {
                throw ValidationException::withMessages(['production_order_id' => 'Production tasks have already been generated for this production order.']);
            }

            $steps = $productionOrder->operationSequence->steps->values();
            $factoryUnit = $steps->first()->factoryUnit;
            $quantity = max(1, (int) (float) $productionOrder->quantity);
            $created = 0;

            for ($item = 0; $item < $quantity; $item++) {
                $itemInstance = ItemInstance::query()->create([
                    'item_id' => $productionOrder->item_id,
                    'serial_number' => $this->nextSerialNumber($factoryUnit->code),
                    'factory_unit_id' => $factoryUnit->id,
                    'current_location_id' => null,
                    'current_status' => ItemInstanceStatus::Planned->value,
                    'production_order_id' => $productionOrder->id,
                ]);

                foreach ($steps as $index => $step) {
                    ProductionTask::query()->create([
                        'production_order_id' => $productionOrder->id,
                        'item_instance_id' => $itemInstance->id,
                        'operation_sequence_step_id' => $step->id,
                        'employee_id' => $employee->id,
                        'status' => $index === 0 ? ProductionTaskStatus::Ready->value : ProductionTaskStatus::Planned->value,
                    ]);

                    $created++;
                }
            }

            $productionOrder->update([
                'status' => ProductionOrderStatus::Released->value,
            ]);

            $this->auditLogService->log('production_tasks_generated', $productionOrder, [
                'generated_count' => $created,
                'employee_id' => $employee->id,
            ], $causer);

            return $created;
        });
    }

    public function start(ProductionTask $productionTask, ?User $causer = null): ProductionTask
    {
        if ($productionTask->status !== ProductionTaskStatus::Ready) {
            throw ValidationException::withMessages(['status' => 'Only ready production tasks can be started.']);
        }

        return DB::transaction(function () use ($productionTask, $causer): ProductionTask {
            $productionTask->update([
                'status' => ProductionTaskStatus::InProgress->value,
                'started_at' => now(),
            ]);

            $productionTask->itemInstance->update([
                'current_status' => ItemInstanceStatus::InProduction->value,
            ]);

            $productionTask->productionOrder?->update([
                'status' => ProductionOrderStatus::InProgress->value,
                'started_at' => $productionTask->productionOrder->started_at ?? now(),
            ]);

            $this->auditLogService->log('production_task_started', $productionTask, [], $causer);

            return $this->productionTasks->findForShow($productionTask->refresh());
        });
    }

    public function finish(ProductionTask $productionTask, ?User $causer = null): ProductionTask
    {
        if ($productionTask->status !== ProductionTaskStatus::InProgress) {
            throw ValidationException::withMessages(['status' => 'Only in-progress production tasks can be finished.']);
        }

        return DB::transaction(function () use ($productionTask, $causer): ProductionTask {
            $productionTask->load('operationSequenceStep', 'itemInstance');
            $status = $productionTask->operationSequenceStep->requires_quality_check
                ? ProductionTaskStatus::WaitingForCheck
                : ProductionTaskStatus::Completed;

            $productionTask->update([
                'status' => $status->value,
                'finished_at' => now(),
            ]);

            if ($status === ProductionTaskStatus::WaitingForCheck) {
                $productionTask->itemInstance->update([
                    'current_status' => ItemInstanceStatus::WaitingForCheck->value,
                ]);
            } else {
                $this->advanceNextTaskOrFinalize($productionTask, $causer);
            }

            $this->auditLogService->log('production_task_finished', $productionTask, [
                'requires_quality_check' => $productionTask->operationSequenceStep->requires_quality_check,
            ], $causer);

            return $this->productionTasks->findForShow($productionTask->refresh());
        });
    }

    public function advanceNextTaskOrFinalize(ProductionTask $productionTask, ?User $causer = null): void
    {
        $productionTask->load('operationSequenceStep', 'itemInstance');

        $nextTask = ProductionTask::query()
            ->where('item_instance_id', $productionTask->item_instance_id)
            ->whereHas('operationSequenceStep', fn ($query) => $query->where('step_order', '>', $productionTask->operationSequenceStep->step_order))
            ->with('operationSequenceStep')
            ->get()
            ->sortBy('operationSequenceStep.step_order')
            ->first();

        if ($nextTask !== null) {
            if ($nextTask->status === ProductionTaskStatus::Planned) {
                $nextTask->update(['status' => ProductionTaskStatus::Ready->value]);
            }

            $productionTask->itemInstance->update([
                'current_status' => ItemInstanceStatus::InProduction->value,
            ]);

            return;
        }

        $productionTask->itemInstance->update([
            'current_status' => ItemInstanceStatus::InStock->value,
        ]);

        $this->completeProductionOrderWhenReady($productionTask->productionOrder, $causer);
    }

    private function completeProductionOrderWhenReady(?ProductionOrder $productionOrder, ?User $causer = null): void
    {
        if ($productionOrder === null) {
            return;
        }

        $hasOpenTask = ProductionTask::query()
            ->where('production_order_id', $productionOrder->id)
            ->where('status', '!=', ProductionTaskStatus::Completed->value)
            ->exists();

        if ($hasOpenTask) {
            return;
        }

        $productionOrder->update([
            'status' => ProductionOrderStatus::Completed->value,
            'finished_at' => now(),
        ]);

        $this->auditLogService->log('production_order_completed', $productionOrder, [], $causer);
    }

    private function nextSerialNumber(string $prefix): string
    {
        $year = (int) now()->format('Y');
        $sequence = SerialSequence::query()->firstOrCreate(
            ['prefix' => $prefix, 'year' => $year],
            ['last_number' => 0]
        );

        $sequence->increment('last_number');

        return sprintf('%s/%d/%04d', $prefix, $year, $sequence->last_number);
    }
}
