<?php

namespace App\Services\Admin;

use App\Models\CustomerOrder;
use App\Repositories\Contracts\CapacityRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CapacityPlanningService
{
    public function __construct(
        private readonly CapacityRepositoryInterface $capacity,
        private readonly LeadTimeEstimator $leadTimeEstimator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return Cache::remember('capacity.dashboard', 60, function (): array {
            $factoryLoads = $this->factoryUnitLoads();
            $employeeLoads = $this->employeeLoads();
            $schedule = $this->scheduleRows();

            $overloaded = $factoryLoads
                ->filter(fn (array $load): bool => $load['utilization'] >= 90)
                ->sortByDesc('utilization')
                ->values();

            $averageUtilization = round((float) $factoryLoads->avg('utilization'), 1);
            $averageLeadTime = round((float) $schedule->avg('duration'), 1);
            $atRiskOrders = $this->ordersLikelyToMissDeadline();

            return [
                'summary' => [
                    'current_factory_load' => (int) $factoryLoads->sum('reserved_minutes'),
                    'employee_load' => (int) $employeeLoads->sum('reserved_minutes'),
                    'overloaded_factory_units' => $overloaded->count(),
                    'available_capacity' => (int) $factoryLoads->sum('available_minutes'),
                    'delayed_production_orders' => $atRiskOrders->count(),
                    'average_utilization' => $averageUtilization,
                    'average_lead_time' => $averageLeadTime,
                ],
                'top_overloaded_factory_units' => $overloaded->take(5)->values(),
                'top_busiest_employees' => $employeeLoads->sortByDesc('utilization')->take(5)->values(),
                'orders_likely_to_miss_deadline' => $atRiskOrders,
                'factory_loads' => $factoryLoads->take(8)->values(),
                'employee_loads' => $employeeLoads->take(8)->values(),
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function factoryUnitLoads(): Collection
    {
        return Cache::remember(
            'capacity.factory-units',
            60,
            fn (): Collection => $this->capacity->factoryUnitLoads(now()->startOfDay(), now()->addDays(7)->endOfDay()),
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function employeeLoads(): Collection
    {
        return Cache::remember(
            'capacity.employees',
            60,
            fn (): Collection => $this->capacity->employeeLoads(now()->startOfDay(), now()->addDays(7)->endOfDay()),
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function scheduleRows(): Collection
    {
        return Cache::remember(
            'capacity.schedule',
            60,
            fn (): Collection => $this->capacity->scheduleRows(now()->subDay()->startOfDay(), now()->addDays(14)->endOfDay()),
        );
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    public function productionOrderOptions(): Collection
    {
        return $this->capacity->productionOrderOptions();
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    public function customerOrderOptions(): Collection
    {
        return $this->capacity->customerOrderOptions();
    }

    public function forgetCapacityCache(): void
    {
        Cache::forget('capacity.dashboard');
        Cache::forget('capacity.factory-units');
        Cache::forget('capacity.employees');
        Cache::forget('capacity.schedule');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function ordersLikelyToMissDeadline(): Collection
    {
        return CustomerOrder::query()
            ->whereNotNull('requested_delivery_date')
            ->orderBy('requested_delivery_date')
            ->limit(20)
            ->get()
            ->map(function (CustomerOrder $order): array {
                $estimate = $this->leadTimeEstimator->estimate($order);

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'requested_delivery_date' => $order->requested_delivery_date?->toDateString(),
                    'estimated_finish' => $estimate['estimatedFinish'],
                    'late_by_minutes' => $estimate['lateByMinutes'],
                    'critical_factory_unit' => $estimate['criticalFactoryUnit'],
                ];
            })
            ->filter(fn (array $order): bool => $order['late_by_minutes'] > 0)
            ->take(5)
            ->values();
    }
}
