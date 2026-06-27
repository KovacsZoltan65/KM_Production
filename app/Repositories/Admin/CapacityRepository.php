<?php

namespace App\Repositories\Admin;

use App\Models\CapacityReservation;
use App\Models\CustomerOrder;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\FactoryUnitCalendar;
use App\Models\ProductionOrder;
use App\Repositories\Contracts\CapacityRepositoryInterface;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class CapacityRepository implements CapacityRepositoryInterface
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function factoryUnitLoads(CarbonInterface $from, CarbonInterface $until): Collection
    {
        $reservedByUnit = CapacityReservation::query()
            ->selectRaw('factory_unit_id, SUM(planned_minutes) as reserved_minutes, COUNT(*) as queue_count')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('reserved_from', [$from, $until])
            ->groupBy('factory_unit_id')
            ->get()
            ->keyBy('factory_unit_id');

        return FactoryUnit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (FactoryUnit $factoryUnit) use ($reservedByUnit, $from, $until): array {
                $reserved = (int) ($reservedByUnit->get($factoryUnit->id)?->reserved_minutes ?? 0);
                $available = $this->factoryAvailableMinutes($factoryUnit, $from, $until);
                $utilization = $available > 0 ? round(($reserved / $available) * 100, 1) : 0.0;

                return [
                    'id' => $factoryUnit->id,
                    'factory_unit' => $factoryUnit->name,
                    'code' => $factoryUnit->code,
                    'available_minutes' => $available,
                    'reserved_minutes' => $reserved,
                    'utilization' => $utilization,
                    'current_queue' => (int) ($reservedByUnit->get($factoryUnit->id)?->queue_count ?? 0),
                    'status' => $this->utilizationStatus($utilization),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function employeeLoads(CarbonInterface $from, CarbonInterface $until): Collection
    {
        $reservedByEmployee = CapacityReservation::query()
            ->selectRaw('employee_id, SUM(planned_minutes) as reserved_minutes, COUNT(*) as assigned_tasks')
            ->whereNotNull('employee_id')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('reserved_from', [$from, $until])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        return Employee::query()
            ->with('professionalRole')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($reservedByEmployee, $from, $until): array {
                $reserved = (int) ($reservedByEmployee->get($employee->id)?->reserved_minutes ?? 0);
                $working = $this->employeeWorkingMinutes($employee, $from, $until);
                $utilization = $working > 0 ? round(($reserved / $working) * 100, 1) : 0.0;

                return [
                    'id' => $employee->id,
                    'employee' => $employee->name,
                    'professional_role' => $employee->professionalRole?->name ?? '-',
                    'working_minutes' => $working,
                    'reserved_minutes' => $reserved,
                    'utilization' => $utilization,
                    'assigned_tasks' => (int) ($reservedByEmployee->get($employee->id)?->assigned_tasks ?? 0),
                    'status' => $this->utilizationStatus($utilization),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function scheduleRows(CarbonInterface $from, CarbonInterface $until): Collection
    {
        return CapacityReservation::query()
            ->with([
                'factoryUnit',
                'employee',
                'productionTask.productionOrder',
                'productionTask.operationSequenceStep.operationType',
            ])
            ->whereBetween('reserved_from', [$from, $until])
            ->orderBy('reserved_from')
            ->get()
            ->map(fn (CapacityReservation $reservation): array => [
                'id' => $reservation->id,
                'factory_unit' => $reservation->factoryUnit?->name ?? '-',
                'production_task' => sprintf(
                    '%s / %s',
                    $reservation->productionTask?->productionOrder?->order_number ?? '-',
                    $reservation->productionTask?->operationSequenceStep?->operationType?->name ?? 'Task',
                ),
                'start' => $reservation->reserved_from?->toDateTimeString(),
                'finish' => $reservation->reserved_until?->toDateTimeString(),
                'duration' => $reservation->planned_minutes,
                'employee' => $reservation->employee?->name ?? '-',
                'status' => $reservation->status,
            ]);
    }

    public function productionOrderOptions(): Collection
    {
        return ProductionOrder::query()
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'order_number'])
            ->map(fn (ProductionOrder $order): array => [
                'id' => $order->id,
                'label' => $order->order_number,
            ]);
    }

    public function customerOrderOptions(): Collection
    {
        return CustomerOrder::query()
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'order_number'])
            ->map(fn (CustomerOrder $order): array => [
                'id' => $order->id,
                'label' => $order->order_number,
            ]);
    }

    public function tasksForProductionOrder(ProductionOrder $productionOrder): EloquentCollection
    {
        return $productionOrder->productionTasks()
            ->with(['employee', 'operationSequenceStep.factoryUnit', 'operationSequenceStep.professionalRole', 'operationSequenceStep.operationType'])
            ->join('operation_sequence_steps', 'operation_sequence_steps.id', '=', 'production_tasks.operation_sequence_step_id')
            ->orderBy('operation_sequence_steps.step_order')
            ->select('production_tasks.*')
            ->get();
    }

    public function factoryCalendarFor(int $factoryUnitId, int $weekday): ?FactoryUnitCalendar
    {
        return FactoryUnitCalendar::query()
            ->where('factory_unit_id', $factoryUnitId)
            ->where('weekday', $weekday)
            ->first();
    }

    public function nextFactoryReservationOverlap(int $factoryUnitId, CarbonInterface $from, CarbonInterface $until, ?int $ignoreProductionTaskId = null): ?CapacityReservation
    {
        return CapacityReservation::query()
            ->where('factory_unit_id', $factoryUnitId)
            ->where('status', '!=', 'cancelled')
            ->where('reserved_from', '<', $until)
            ->where('reserved_until', '>', $from)
            ->when($ignoreProductionTaskId !== null, fn ($query) => $query->where('production_task_id', '!=', $ignoreProductionTaskId))
            ->orderBy('reserved_until')
            ->first();
    }

    public function createOrUpdateReservation(array $attributes, array $values): CapacityReservation
    {
        return CapacityReservation::query()->updateOrCreate($attributes, $values);
    }

    public function productionOrdersForCustomerOrder(CustomerOrder $customerOrder): EloquentCollection
    {
        return ProductionOrder::query()
            ->whereHas('customerOrderItem', fn ($query) => $query->where('customer_order_id', $customerOrder->id))
            ->with(['productionTasks.operationSequenceStep.factoryUnit', 'productionTasks.employee'])
            ->get();
    }

    public function reservationCount(): int
    {
        return CapacityReservation::query()->count();
    }

    private function factoryAvailableMinutes(FactoryUnit $factoryUnit, CarbonInterface $from, CarbonInterface $until): int
    {
        $calendars = FactoryUnitCalendar::query()
            ->where('factory_unit_id', $factoryUnit->id)
            ->where('is_working_day', true)
            ->get()
            ->keyBy('weekday');

        if ($calendars->isEmpty()) {
            return (int) ($factoryUnit->daily_capacity_minutes ?? 480) * max(1, $from->diffInDays($until));
        }

        $minutes = 0;
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $until->copy()->startOfDay()) as $day) {
            $calendar = $calendars->get($day->dayOfWeekIso);
            if ($calendar === null) {
                continue;
            }

            $minutes += max(0, $day->copy()->setTimeFromTimeString($calendar->work_start)->diffInMinutes($day->copy()->setTimeFromTimeString($calendar->work_end)) - $calendar->break_minutes);
        }

        return (int) $minutes;
    }

    private function employeeWorkingMinutes(Employee $employee, CarbonInterface $from, CarbonInterface $until): int
    {
        $calendars = $employee->workCalendars()
            ->get()
            ->keyBy('weekday');

        if ($calendars->isEmpty()) {
            return 450 * max(1, $from->diffInDays($until));
        }

        $minutes = 0;
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $until->copy()->startOfDay()) as $day) {
            $calendar = $calendars->get($day->dayOfWeekIso);
            if ($calendar === null) {
                continue;
            }

            $minutes += max(0, $day->copy()->setTimeFromTimeString($calendar->work_start)->diffInMinutes($day->copy()->setTimeFromTimeString($calendar->work_end)) - $calendar->break_minutes);
        }

        return (int) $minutes;
    }

    private function utilizationStatus(float $utilization): string
    {
        return match (true) {
            $utilization >= 90 => 'red',
            $utilization >= 70 => 'yellow',
            default => 'green',
        };
    }
}
