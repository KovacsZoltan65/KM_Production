<?php

namespace App\Services\Admin;

use App\Models\CustomerOrder;
use App\Repositories\Contracts\CapacityRepositoryInterface;
use App\Services\BusinessCacheInvalidator;
use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CapacityPlanningService
{
    public function __construct(
        private readonly CapacityRepositoryInterface $capacity,
        private readonly LeadTimeEstimator $leadTimeEstimator,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    /**
     * Összeállítja és rövid időre gyorsítótárazza a kapacitási irányítópultot.
     *
     * @return array{
     *     summary: array{
     *         current_factory_load: int,
     *         employee_load: int,
     *         overloaded_factory_units: int,
     *         available_capacity: int,
     *         delayed_production_orders: int,
     *         average_utilization: float,
     *         average_lead_time: float
     *     },
     *     top_overloaded_factory_units: Collection<int, array{id: int, factory_unit: string,
     *         code: string, available_minutes: int, reserved_minutes: int,
     *         utilization: float, current_queue: int, status: string}>,
     *     top_busiest_employees: Collection<int, array{id: int, employee: string,
     *         professional_role: string, working_minutes: int, reserved_minutes: int,
     *         utilization: float, assigned_tasks: int, status: string}>,
     *     orders_likely_to_miss_deadline: Collection<int, array{id: int, order_number: string,
     *         requested_delivery_date: string, estimated_finish: string,
     *         late_by_minutes: positive-int, critical_factory_unit: string}>,
     *     factory_loads: Collection<int, array{id: int, factory_unit: string, code: string,
     *         available_minutes: int, reserved_minutes: int, utilization: float,
     *         current_queue: int, status: string}>,
     *     employee_loads: Collection<int, array{id: int, employee: string,
     *         professional_role: string, working_minutes: int, reserved_minutes: int,
     *         utilization: float, assigned_tasks: int, status: string}>
     * } A kapacitási összesítések és kiemelt listák.
     */
    public function dashboard(): array
    {
        return Cache::remember(BusinessCacheKey::make(BusinessCacheDomain::Capacity, 'dashboard'), 60, function (): array {
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
     * Visszaadja a következő hét gyorsítótárazott gyáregység-terhelését.
     *
     * @return Collection<int, array{id: int, factory_unit: string, code: string,
     *     available_minutes: int, reserved_minutes: int, utilization: float,
     *     current_queue: int, status: string}> A gyáregységek terhelési sorai.
     */
    public function factoryUnitLoads(): Collection
    {
        return Cache::remember(
            BusinessCacheKey::make(BusinessCacheDomain::Capacity, 'factory-units'),
            60,
            fn (): Collection => $this->capacity->factoryUnitLoads(now()->startOfDay(), now()->addDays(7)->endOfDay()),
        );
    }

    /**
     * Visszaadja a következő hét gyorsítótárazott dolgozói terhelését.
     *
     * @return Collection<int, array{id: int, employee: string, professional_role: string,
     *     working_minutes: int, reserved_minutes: int, utilization: float,
     *     assigned_tasks: int, status: string}> A dolgozók terhelési sorai.
     */
    public function employeeLoads(): Collection
    {
        return Cache::remember(
            BusinessCacheKey::make(BusinessCacheDomain::Capacity, 'employees'),
            60,
            fn (): Collection => $this->capacity->employeeLoads(now()->startOfDay(), now()->addDays(7)->endOfDay()),
        );
    }

    /**
     * Visszaadja a kapacitásütemezés gyorsítótárazott sorait.
     *
     * @return Collection<int, covariant array{id: int, factory_unit: string,
     *     production_task: non-falsy-string, start: mixed, finish: mixed,
     *     duration: int<0, max>, employee: string, status: string}> Az ütemezési sorok.
     */
    public function scheduleRows(): Collection
    {
        return Cache::remember(
            BusinessCacheKey::make(BusinessCacheDomain::Capacity, 'schedule'),
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

    /**
     * Érvényteleníti a kapacitástervezés összes gyorsítótár-bejegyzését.
     */
    public function forgetCapacityCache(): void
    {
        $this->cacheInvalidator->capacityChanged();
    }

    /**
     * Kiválasztja az öt legnagyobb késési kockázatú vevői rendelést.
     *
     * @return Collection<int, array{id: int, order_number: string,
     *     requested_delivery_date: string, estimated_finish: string,
     *     late_by_minutes: positive-int, critical_factory_unit: string}> A kockázatos rendelések.
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
