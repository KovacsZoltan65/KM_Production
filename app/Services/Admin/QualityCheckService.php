<?php

namespace App\Services\Admin;

use App\Enums\ItemInstanceStatus;
use App\Enums\ProductionTaskStatus;
use App\Enums\QualityCheckResult;
use App\Models\ProductionTask;
use App\Models\QualityCheck;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A gyártási feladatok minőségellenőrzését és eredményfüggő állapotváltásait kezeli.
 *
 * Az ellenőrzési rekordot, a feladat továbbléptetését és az auditot egy
 * tranzakciós folyamatban koordinálja.
 */
class QualityCheckService
{
    public function __construct(
        private readonly ProductionTaskService $productionTaskService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(ProductionTask $productionTask, array $attributes, ?User $causer = null): QualityCheck
    {
        if ($productionTask->status !== ProductionTaskStatus::WaitingForCheck) {
            throw ValidationException::withMessages(['status' => __('quality.validation.task_status')]);
        }

        return DB::transaction(function () use ($productionTask, $attributes, $causer): QualityCheck {
            $qualityCheck = QualityCheck::query()->create([
                'production_task_id' => $productionTask->id,
                'checked_by' => $attributes['checked_by'],
                'result' => $attributes['result'],
                'checked_at' => now(),
                'notes' => $attributes['notes'] ?? null,
            ]);

            if ($qualityCheck->result === QualityCheckResult::Accepted) {
                $productionTask->update(['status' => ProductionTaskStatus::Completed->value]);
                $productionTask->itemInstance->update(['current_status' => ItemInstanceStatus::Checked->value]);
                $this->productionTaskService->advanceNextTaskOrFinalize($productionTask->refresh(), $causer);
            } else {
                $productionTask->update(['status' => ProductionTaskStatus::Rejected->value]);
                $productionTask->itemInstance->update(['current_status' => ItemInstanceStatus::Rejected->value]);
                $this->auditLogService->log('production_task_rejected', $productionTask, [
                    'result' => $qualityCheck->result->value,
                ], $causer);
            }

            $this->auditLogService->log('production_task_quality_checked', $qualityCheck, [
                'production_task_id' => $productionTask->id,
                'result' => $qualityCheck->result->value,
            ], $causer);

            return $qualityCheck->load('inspector');
        });
    }
}
