<?php

namespace Tests\Feature;

use App\Models\CapacityReservation;
use App\Models\CustomerOrder;
use App\Models\FactoryUnitCalendar;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Models\User;
use App\Services\Admin\CapacityPlanningService;
use App\Services\Admin\CapacitySlotFinder;
use App\Services\Admin\LeadTimeEstimator;
use App\Services\Admin\SchedulingService;
use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CapacityPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Cache::flush();
    }

    public function test_capacity_dashboard_is_permission_protected(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
            ->get(route('admin.capacity.dashboard'))
            ->assertForbidden();
    }

    public function test_worker_cannot_access_capacity_dashboard(): void
    {
        $this->actingAs($this->userWithRole('worker'))
            ->get(route('admin.capacity.dashboard'))
            ->assertForbidden();
    }

    public function test_viewer_can_view_but_cannot_schedule(): void
    {
        $order = ProductionOrder::query()->firstOrFail();

        $this->actingAs($this->userWithRole('viewer'))
            ->get(route('admin.capacity.dashboard'))
            ->assertOk();

        $this->actingAs($this->userWithRole('viewer', 'viewer2@example.com'))
            ->post(route('admin.capacity.schedule.store'), ['production_order_id' => $order->id])
            ->assertForbidden();
    }

    public function test_capacity_override_requires_super_admin_permission(): void
    {
        $order = ProductionOrder::query()->firstOrFail();

        $this->actingAs($this->userWithRole('production-manager'))
            ->post(route('admin.capacity.schedule.store'), [
                'production_order_id' => $order->id,
                'override' => true,
            ])
            ->assertForbidden();
    }

    public function test_production_manager_can_schedule(): void
    {
        $order = ProductionOrder::query()->firstOrFail();

        $this->actingAs($this->userWithRole('production-manager'))
            ->post(route('admin.capacity.schedule.store'), ['production_order_id' => $order->id])
            ->assertRedirect();

        $this->assertDatabaseHas('capacity_reservations', [
            'production_task_id' => ProductionTask::query()->firstOrFail()->id,
        ]);
    }

    public function test_factory_load_and_utilization_are_calculated(): void
    {
        $task = ProductionTask::query()->with('operationSequenceStep')->firstOrFail();
        CapacityReservation::factory()->create([
            'production_task_id' => $task->id,
            'factory_unit_id' => $task->operationSequenceStep->factory_unit_id,
            'employee_id' => $task->employee_id,
            'reserved_from' => now()->addDay()->setTime(8, 0),
            'reserved_until' => now()->addDay()->setTime(10, 0),
            'planned_minutes' => 120,
        ]);

        $load = app(CapacityPlanningService::class)->factoryUnitLoads()
            ->firstWhere('id', $task->operationSequenceStep->factory_unit_id);

        $this->assertSame(120, $load['reserved_minutes']);
        $this->assertGreaterThan(0, $load['available_minutes']);
        $this->assertGreaterThan(0, $load['utilization']);
    }

    public function test_employee_load_is_calculated(): void
    {
        $task = ProductionTask::query()->with('operationSequenceStep')->firstOrFail();
        CapacityReservation::factory()->create([
            'production_task_id' => $task->id,
            'factory_unit_id' => $task->operationSequenceStep->factory_unit_id,
            'employee_id' => $task->employee_id,
            'reserved_from' => now()->addDay()->setTime(8, 0),
            'reserved_until' => now()->addDay()->setTime(9, 0),
            'planned_minutes' => 60,
        ]);

        $load = app(CapacityPlanningService::class)->employeeLoads()
            ->firstWhere('id', $task->employee_id);

        $this->assertSame(60, $load['reserved_minutes']);
        $this->assertSame(1, $load['assigned_tasks']);
        $this->assertGreaterThan(0, $load['utilization']);
    }

    public function test_reservation_is_created_during_schedule_and_operation_order_is_preserved(): void
    {
        $order = ProductionOrder::query()->firstOrFail();

        app(SchedulingService::class)->schedule($order);

        $reservations = CapacityReservation::query()
            ->orderBy('reserved_from')
            ->get();

        $this->assertNotEmpty($reservations);
        $this->assertTrue($reservations->first()->reserved_from->lessThanOrEqualTo($reservations->last()->reserved_until));
    }

    public function test_working_calendar_is_considered(): void
    {
        $task = ProductionTask::query()->with('operationSequenceStep')->firstOrFail();
        $task->operationSequenceStep->update(['estimated_duration_minutes' => 60]);
        CapacityReservation::query()->delete();

        FactoryUnitCalendar::query()->updateOrCreate(
            [
                'factory_unit_id' => $task->operationSequenceStep->factory_unit_id,
                'weekday' => 3,
            ],
            [
                'work_start' => '09:00:00',
                'work_end' => '10:00:00',
                'break_minutes' => 0,
                'is_working_day' => true,
            ],
        );

        app(SchedulingService::class)->schedule($task->productionOrder);

        $reservation = CapacityReservation::query()->where('production_task_id', $task->id)->firstOrFail();

        $this->assertSame('09:00:00', $reservation->reserved_from->format('H:i:s'));
    }

    public function test_simulation_does_not_create_capacity_reservations(): void
    {
        $count = CapacityReservation::query()->count();
        $order = CustomerOrder::query()->firstOrFail();

        $this->actingAs($this->userWithRole('production-manager'))
            ->post(route('admin.capacity.simulate.run'), ['customer_order_id' => $order->id])
            ->assertOk();

        $this->assertSame($count, CapacityReservation::query()->count());
    }

    public function test_lead_time_estimate_returns_dates_and_late_flag(): void
    {
        $order = CustomerOrder::query()->firstOrFail();
        $order->update(['requested_delivery_date' => '2026-06-30']);

        $estimate = app(LeadTimeEstimator::class)->estimate($order);

        $this->assertNotEmpty($estimate['estimatedStart']);
        $this->assertNotEmpty($estimate['estimatedFinish']);
        $this->assertTrue($estimate['isLate']);
        $this->assertGreaterThan(0, $estimate['lateByMinutes']);
    }

    public function test_capacity_cache_keeps_first_result_for_sixty_seconds(): void
    {
        $service = app(CapacityPlanningService::class);
        $first = $service->factoryUnitLoads();

        $task = ProductionTask::query()->with('operationSequenceStep')->firstOrFail();
        CapacityReservation::factory()->create([
            'production_task_id' => $task->id,
            'factory_unit_id' => $task->operationSequenceStep->factory_unit_id,
            'employee_id' => $task->employee_id,
            'planned_minutes' => 300,
        ]);

        $second = $service->factoryUnitLoads();

        $this->assertTrue(Cache::has(BusinessCacheKey::make(BusinessCacheDomain::Capacity, 'factory-units')));
        $this->assertSame($first->toArray(), $second->toArray());
    }

    public function test_capacity_dashboard_query_count_is_bounded(): void
    {
        Cache::flush();

        $queries = $this->countQueries(fn (): array => app(CapacityPlanningService::class)->dashboard());

        $this->assertLessThan(20, $queries);
    }

    public function test_slot_finding_reuses_calendar_queries(): void
    {
        CapacityReservation::query()->delete();

        $task = ProductionTask::query()->with('operationSequenceStep')->firstOrFail();
        $factoryUnitId = $task->operationSequenceStep->factory_unit_id;
        $workingCalendar = FactoryUnitCalendar::query()
            ->where('factory_unit_id', $factoryUnitId)
            ->where('is_working_day', true)
            ->firstOrFail();
        $workday = now()->startOfWeek()->addDays($workingCalendar->weekday - 1);
        $finder = app(CapacitySlotFinder::class);

        $queries = $this->countQueries(function () use ($finder, $factoryUnitId, $task, $workday): void {
            $finder->findSlot($factoryUnitId, $workday->copy()->addHours(8), 30, $task->id);
            $finder->findSlot($factoryUnitId, $workday->copy()->addHours(9), 30, $task->id);
            $finder->findSlot($factoryUnitId, $workday->copy()->addHours(10), 30, $task->id);
        });

        $this->assertLessThan(3, $queries);
    }

    public function test_schedule_and_simulation_are_audited(): void
    {
        $order = ProductionOrder::query()->firstOrFail();
        $customerOrder = CustomerOrder::query()->firstOrFail();

        app(SchedulingService::class)->schedule($order);
        app(LeadTimeEstimator::class)->estimate($customerOrder, audit: true);

        $this->assertTrue(Activity::query()->where('event', 'capacity_schedule_generated')->exists());
        $this->assertTrue(Activity::query()->where('event', 'capacity_simulation_run')->exists());
    }

    public function test_capacity_override_is_audited(): void
    {
        $order = ProductionOrder::query()->firstOrFail();

        app(SchedulingService::class)->schedule($order, override: true);

        $this->assertTrue(Activity::query()->where('event', 'capacity_override')->exists());
    }

    public function test_route_helper_contains_capacity_routes(): void
    {
        $routes = file_get_contents(resource_path('js/Utils/routes.js'));

        $this->assertStringContainsString("'admin.capacity.dashboard'", $routes);
        $this->assertStringContainsString("'admin.capacity.schedule.store'", $routes);
        $this->assertStringContainsString("'admin.capacity.simulate.run'", $routes);
    }

    public function test_capacity_pages_build_inertia_payloads(): void
    {
        $user = $this->userWithRole('production-manager');

        $this->actingAs($user)->get(route('admin.capacity.factory-units'))->assertOk();
        $this->actingAs($user)->get(route('admin.capacity.employees'))->assertOk();
        $this->actingAs($user)->get(route('admin.capacity.schedule'))->assertOk();
        $this->actingAs($user)->get(route('admin.capacity.simulate'))->assertOk();
    }

    private function userWithRole(string $role, string $email = 'capacity-user@example.com'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function countQueries(callable $callback): int
    {
        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        $callback();

        return $queries;
    }
}
