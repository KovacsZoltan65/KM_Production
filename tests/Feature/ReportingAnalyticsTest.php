<?php

namespace Tests\Feature;

use App\Enums\CustomerOrderStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\ProductionOrderStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\ProductionTaskStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QualityCheckResult;
use App\Enums\StockReservationStatus;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\Document;
use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\Location;
use App\Models\MaterialRequirement;
use App\Models\OperationSequenceStep;
use App\Models\ProductionOrder;
use App\Models\ProductionPlan;
use App\Models\ProductionTask;
use App\Models\PurchaseOrder;
use App\Models\QualityCheck;
use App\Models\StockBalance;
use App\Models\StockReservation;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Admin\DashboardService;
use App\Services\Admin\ReportingService;
use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReportingAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dashboard_is_permission_protected(): void
    {
        $this->actingAs($this->verifiedUser('worker'))
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_kpis_are_correct(): void
    {
        $this->dashboardFixture();

        $this->actingAs($this->verifiedUser('viewer'))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Dashboard')
                ->where('summary.kpis.ready_production_tasks', ProductionTask::query()->where('status', ProductionTaskStatus::Ready->value)->count())
                ->where('summary.kpis.documents_waiting_approval', Document::query()->where('approved', false)->count())
                ->where('summary.kpis.shortages', MaterialRequirement::query()->where('missing_quantity', '>', 0)->count()));
    }

    public function test_customer_orders_report_filter_works(): void
    {
        $customer = Customer::factory()->create(['name' => 'Needle Customer']);
        CustomerOrder::factory()->create(['customer_id' => $customer->id, 'status' => CustomerOrderStatus::Confirmed]);
        CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Completed]);

        $this->actingAs($this->verifiedUser('viewer'))
            ->get(route('admin.reports.customer-orders', [
                'status' => CustomerOrderStatus::Confirmed->value,
                'customer_id' => $customer->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Reports/CustomerOrders')
                ->has('report.rows', 1)
                ->where('report.rows.0.customer', 'Needle Customer'));
    }

    public function test_production_report_completed_percent_is_correct(): void
    {
        $order = ProductionOrder::factory()->create(['order_number' => 'PRD-REPORT-001']);
        $step = OperationSequenceStep::factory()->create(['operation_sequence_id' => $order->operation_sequence_id]);
        ProductionTask::factory()->count(2)->create(['production_order_id' => $order->id, 'operation_sequence_step_id' => $step->id, 'status' => ProductionTaskStatus::Completed]);
        ProductionTask::factory()->count(2)->create(['production_order_id' => $order->id, 'operation_sequence_step_id' => $step->id, 'status' => ProductionTaskStatus::Ready]);

        $report = app(ReportingService::class)->productionSummary();
        $row = collect($report['rows'])->firstWhere('production_order', 'PRD-REPORT-001');

        $this->assertSame(2, $row['completed_tasks']);
        $this->assertSame(4, $row['all_tasks']);
        $this->assertSame(50.0, $row['completed_percent']);
    }

    public function test_inventory_available_quantity_is_correct(): void
    {
        [$item, $location] = $this->stockFixture(quantity: 10, reserved: 4);

        $report = app(ReportingService::class)->inventorySummary();
        $row = collect($report['rows'])->first(fn (array $row): bool => str_contains($row['item'], $item->item_number) && str_contains($row['location'], $location->code));

        $this->assertSame(10.0, $row['current_stock']);
        $this->assertSame(4.0, $row['reserved']);
        $this->assertSame(6.0, $row['available']);
    }

    public function test_inventory_shortage_highlight_flag_works(): void
    {
        [$item] = $this->stockFixture(quantity: 2, reserved: 3);

        $report = app(ReportingService::class)->inventorySummary();
        $row = collect($report['rows'])->first(fn (array $row): bool => str_contains($row['item'], $item->item_number));

        $this->assertTrue($row['is_shortage']);
    }

    public function test_procurement_report_summary_is_correct(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Steel Supplier']);
        $open = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id, 'status' => PurchaseOrderStatus::Ordered]);
        PurchaseOrder::factory()->create(['supplier_id' => $supplier->id, 'status' => PurchaseOrderStatus::Received]);
        GoodsReceipt::factory()->create(['purchase_order_id' => $open->id, 'status' => GoodsReceiptStatus::Draft]);

        $report = app(ReportingService::class)->procurementSummary();
        $row = collect($report['rows'])->firstWhere('supplier', 'Steel Supplier');

        $this->assertSame(2, $row['purchase_orders']);
        $this->assertSame(1, $row['open']);
        $this->assertSame(1, $row['closed']);
        $this->assertSame(1, $row['goods_receipts_pending']);
    }

    public function test_quality_acceptance_rate_is_correct(): void
    {
        $order = ProductionOrder::factory()->create(['order_number' => 'QUAL-001']);
        $step = OperationSequenceStep::factory()->create(['operation_sequence_id' => $order->operation_sequence_id]);
        $acceptedA = ProductionTask::factory()->create(['production_order_id' => $order->id, 'operation_sequence_step_id' => $step->id]);
        $acceptedB = ProductionTask::factory()->create(['production_order_id' => $order->id, 'operation_sequence_step_id' => $step->id]);
        $rejected = ProductionTask::factory()->create(['production_order_id' => $order->id, 'operation_sequence_step_id' => $step->id]);
        $rework = ProductionTask::factory()->create(['production_order_id' => $order->id, 'operation_sequence_step_id' => $step->id]);
        QualityCheck::factory()->create(['production_task_id' => $acceptedA->id, 'result' => QualityCheckResult::Accepted]);
        QualityCheck::factory()->create(['production_task_id' => $acceptedB->id, 'result' => QualityCheckResult::Accepted]);
        QualityCheck::factory()->create(['production_task_id' => $rejected->id, 'result' => QualityCheckResult::Rejected]);
        QualityCheck::factory()->create(['production_task_id' => $rework->id, 'result' => QualityCheckResult::ReworkRequired]);

        $report = app(ReportingService::class)->qualitySummary();
        $row = collect($report['rows'])->firstWhere('production_order', 'QUAL-001');

        $this->assertSame(4, $row['quality_checks']);
        $this->assertSame(50.0, $row['acceptance_rate']);
    }

    public function test_shop_floor_average_task_time_is_correct(): void
    {
        $employee = Employee::factory()->create(['name' => 'Welder Alice']);
        $step = OperationSequenceStep::factory()->create();
        ProductionTask::factory()->create([
            'employee_id' => $employee->id,
            'operation_sequence_step_id' => $step->id,
            'status' => ProductionTaskStatus::Completed,
            'started_at' => now()->subHours(3),
            'finished_at' => now()->subHours(2),
        ]);
        ProductionTask::factory()->create([
            'employee_id' => $employee->id,
            'operation_sequence_step_id' => $step->id,
            'status' => ProductionTaskStatus::Completed,
            'started_at' => now()->subHours(3),
            'finished_at' => now()->subHour(),
        ]);

        $report = app(ReportingService::class)->shopFloorSummary();
        $row = collect($report['rows'])->firstWhere('employee', 'Welder Alice');

        $this->assertSame(90.0, $row['average_task_time']);
    }

    public function test_reporting_service_cache_works(): void
    {
        CustomerOrder::factory()->create(['order_number' => 'CACHE-REPORT-001']);

        $service = app(ReportingService::class);
        $first = $service->customerOrdersSummary();
        CustomerOrder::factory()->create(['order_number' => 'CACHE-REPORT-002']);
        $second = $service->customerOrdersSummary();

        $this->assertCount(\count($first['rows']), $second['rows']);
        $this->assertTrue(Cache::has(BusinessCacheKey::make(BusinessCacheDomain::ReportsCustomerOrders, 'summary')));
    }

    public function test_dashboard_cache_works(): void
    {
        CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Confirmed]);

        $service = app(DashboardService::class);
        $first = $service->summary();
        CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Confirmed]);
        $second = $service->summary();

        $this->assertSame($first['kpis']['open_customer_orders'], $second['kpis']['open_customer_orders']);
        $this->assertTrue(Cache::has(BusinessCacheKey::make(BusinessCacheDomain::Dashboard, 'summary')));
    }

    public function test_viewer_can_access_reports(): void
    {
        $this->actingAs($this->verifiedUser('viewer'))
            ->get(route('admin.reports.inventory'))
            ->assertOk();
    }

    public function test_worker_cannot_access_reports(): void
    {
        $this->actingAs($this->verifiedUser('worker'))
            ->get(route('admin.reports.inventory'))
            ->assertForbidden();
    }

    public function test_dashboard_route_helper_exists(): void
    {
        $routes = file_get_contents(resource_path('js/Utils/routes.js'));

        $this->assertStringContainsString("'admin.dashboard'", $routes);
        $this->assertStringContainsString("'admin.reports.production'", $routes);
    }

    private function verifiedUser(?string $role = null): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function dashboardFixture(): void
    {
        CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Confirmed]);
        ProductionPlan::factory()->create(['status' => ProductionPlanStatus::Approved]);
        ProductionOrder::factory()->create(['status' => ProductionOrderStatus::Released]);
        $step = OperationSequenceStep::factory()->create();
        ProductionTask::factory()->create(['operation_sequence_step_id' => $step->id, 'status' => ProductionTaskStatus::Ready]);
        ProductionTask::factory()->create(['operation_sequence_step_id' => $step->id, 'status' => ProductionTaskStatus::InProgress]);
        ProductionTask::factory()->create(['operation_sequence_step_id' => $step->id, 'status' => ProductionTaskStatus::WaitingForCheck]);
        PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Ordered]);
        GoodsReceipt::factory()->create(['status' => GoodsReceiptStatus::Draft]);
        MaterialRequirement::factory()->create(['missing_quantity' => 5]);
        StockBalance::factory()->create(['quantity' => 12]);
        Document::factory()->create(['approved' => false]);
    }

    /**
     * @return array{0: Item, 1: Location}
     */
    private function stockFixture(float $quantity, float $reserved): array
    {
        $item = Item::factory()->create();
        $location = Location::factory()->create();
        StockBalance::factory()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => $quantity,
        ]);
        StockReservation::factory()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'reserved_quantity' => $reserved,
            'status' => StockReservationStatus::Active,
        ]);

        return [$item, $location];
    }
}
