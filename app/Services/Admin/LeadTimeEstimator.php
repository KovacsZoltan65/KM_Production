<?php

namespace App\Services\Admin;

use App\Models\CustomerOrder;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Repositories\Contracts\CapacityRepositoryInterface;
use App\Services\AuditLogService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class LeadTimeEstimator
{
    public function __construct(
        private readonly CapacityRepositoryInterface $capacity,
        private readonly AuditLogService $audit,
        private readonly CapacitySlotFinder $slotFinder,
    ) {}

    /**
     * Megbecsüli a vevői rendelés gyártási időablakát és késési kockázatát.
     *
     * A kapcsolódó gyártási feladatokhoz szabad kapacitásablakokat keres. Az
     * eredményt kérésre auditnaplózza, de foglalást nem hoz létre.
     *
     * @return array{estimatedStart: string, estimatedFinish: string, isLate: bool,
     *     lateByMinutes: int, criticalFactoryUnit: string,
     *     criticalProfessionalRole: string} A számított átfutásibecslés.
     */
    public function estimate(CustomerOrder $order, bool $audit = false): array
    {
        $productionOrders = $this->capacity->productionOrdersForCustomerOrder($order);
        $tasks = $this->tasksForProductionOrders($productionOrders);
        $cursor = $productionOrders
            ->pluck('planned_start_date')
            ->filter()
            ->sort()
            ->first();
        $cursor = CarbonImmutable::parse($cursor ?? now())->startOfDay()->addHours(8);
        $windows = collect();

        foreach ($tasks as $task) {
            $step = $task->operationSequenceStep;
            $duration = max(1, (int) $step->estimated_duration_minutes);

            [$reservedFrom, $reservedUntil] = $this->slotFinder->findSlot(
                $step->factory_unit_id,
                $cursor,
                $duration,
                $task->id,
            );

            $windows->push([
                'task' => $task,
                'from' => $reservedFrom,
                'until' => $reservedUntil,
                'duration' => $duration,
            ]);

            $cursor = $reservedUntil;
        }

        $estimatedMinutes = (int) $tasks->sum(fn ($task): int => (int) $task->operationSequenceStep->estimated_duration_minutes);
        $estimatedStart = $windows->first()['from'] ?? now()->startOfHour();
        $estimatedFinish = $windows->last()['until'] ?? $estimatedStart->copy()->addMinutes(max(1, $estimatedMinutes));
        $deadline = $order->requested_delivery_date?->copy()->endOfDay();
        $lateByMinutes = $deadline !== null && $estimatedFinish->greaterThan($deadline)
            ? $deadline->diffInMinutes($estimatedFinish)
            : 0;

        $result = [
            'estimatedStart' => $estimatedStart->toDateTimeString(),
            'estimatedFinish' => $estimatedFinish->toDateTimeString(),
            'isLate' => $lateByMinutes > 0,
            'lateByMinutes' => $lateByMinutes,
            'criticalFactoryUnit' => $this->criticalFactoryUnit($tasks),
            'criticalProfessionalRole' => $this->criticalProfessionalRole($tasks),
        ];

        if ($audit) {
            $this->audit->log('capacity_simulation_run', $order, [
                'customer_order_id' => $order->id,
                'estimated_finish' => $result['estimatedFinish'],
                'is_late' => $result['isLate'],
            ]);
        }

        return $result;
    }

    /**
     * @param  EloquentCollection<int, ProductionOrder>  $productionOrders
     * @return Collection<int, ProductionTask> A rendelések sorrendbe állított feladatai.
     */
    private function tasksForProductionOrders(EloquentCollection $productionOrders): Collection
    {
        return $productionOrders->flatMap(function (ProductionOrder $productionOrder): Collection {
            if ($productionOrder->relationLoaded('productionTasks')) {
                return $productionOrder->productionTasks;
            }

            return $this->capacity->tasksForProductionOrder($productionOrder);
        });
    }

    /**
     * Meghatározza a legtöbb tervezett időt igénylő gyáregységet.
     *
     * @param  Collection<int, ProductionTask>  $tasks  A vizsgált feladatok.
     */
    private function criticalFactoryUnit(Collection $tasks): string
    {
        return $tasks
            ->groupBy(fn ($task): string => $task->operationSequenceStep->factoryUnit->name)
            ->sortByDesc(fn (Collection $group): int => $group->sum(fn ($task): int => (int) $task->operationSequenceStep->estimated_duration_minutes))
            ->keys()
            ->first() ?? '-';
    }

    /**
     * Meghatározza a legtöbb tervezett időt igénylő szakmai szerepkört.
     *
     * @param  Collection<int, ProductionTask>  $tasks  A vizsgált feladatok.
     */
    private function criticalProfessionalRole(Collection $tasks): string
    {
        return $tasks
            ->groupBy(fn ($task): string => $task->operationSequenceStep->professionalRole->name)
            ->sortByDesc(fn (Collection $group): int => $group->sum(fn ($task): int => (int) $task->operationSequenceStep->estimated_duration_minutes))
            ->keys()
            ->first() ?? '-';
    }
}
