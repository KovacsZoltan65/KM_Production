<?php

namespace Tests\Feature;

use App\Enums\CustomerOrderStatus;
use App\Enums\ProductionOrderStatus;
use App\Enums\ProductionTaskStatus;
use App\Enums\QualityCheckResult;
use App\Enums\StockMovementType;
use App\Models\CapacityReservation;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\FactoryUnit;
use App\Models\FactoryUnitCalendar;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\MaterialRequirement;
use App\Models\OperationSequenceStep;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\QualityCheck;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Admin\BottleneckAnalysisService;
use App\Services\Admin\ManufacturingIntelligenceService;
use App\Services\Admin\MaterialForecastService;
use App\Services\Admin\ProcurementRecommendationService;
use App\Services\Admin\ProductionRiskService;
use App\Services\Admin\QualityTrendService;
use App\Services\Admin\SupplierPerformanceService;
use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ManufacturingIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_intelligence_dashboard_is_permission_protected(): void
    {
        $this->actingAs($this->verifiedUser())
            ->get(route('admin.intelligence.dashboard'))
            ->assertForbidden();
    }

    public function test_viewer_can_access_intelligence_dashboard(): void
    {
        $this->actingAs($this->verifiedUser('viewer'))
            ->get(route('admin.intelligence.dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Intelligence/Dashboard')
                ->has('dashboard.high_risk_orders')
                ->has('dashboard.recommended_purchases'));
    }

    public function test_worker_cannot_access_intelligence_dashboard(): void
    {
        $this->actingAs($this->verifiedUser('worker'))
            ->get(route('admin.intelligence.dashboard'))
            ->assertForbidden();
    }

    public function test_bottleneck_analysis_identifies_utilization_above_90_percent(): void
    {
        $unit = FactoryUnit::factory()->create(['code' => 'BOT', 'name' => 'Bottleneck Unit']);
        FactoryUnitCalendar::factory()->create([
            'factory_unit_id' => $unit->id,
            'weekday' => 1,
            'work_start' => '08:00:00',
            'work_end' => '09:40:00',
            'break_minutes' => 0,
        ]);
        $step = OperationSequenceStep::factory()->create(['factory_unit_id' => $unit->id]);
        $task = ProductionTask::factory()->create(['operation_sequence_step_id' => $step->id, 'status' => ProductionTaskStatus::Ready]);
        CapacityReservation::factory()->create([
            'production_task_id' => $task->id,
            'factory_unit_id' => $unit->id,
            'planned_minutes' => 95,
            'reserved_from' => now()->addDay(),
            'reserved_until' => now()->addDay()->addMinutes(95),
        ]);

        $row = collect(app(BottleneckAnalysisService::class)->analyze()['rows'])->firstWhere('factory_unit', 'BOT - Bottleneck Unit');

        $this->assertSame('bottleneck', $row['status']);
        $this->assertGreaterThanOrEqual(90, $row['utilization_percent']);
    }

    public function test_material_forecast_gives_critical_risk_for_zero_to_three_days_stock(): void
    {
        $item = Item::factory()->purchasedMaterial()->create(['item_number' => 'MAT-CRIT']);
        StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => 3]);
        StockMovement::factory()->create([
            'item_id' => $item->id,
            'quantity' => 30,
            'movement_type' => StockMovementType::ProductionConsume,
            'performed_at' => now()->subDay(),
        ]);

        $row = collect(app(MaterialForecastService::class)->forecast()['rows'])->firstWhere('item_id', $item->id);

        $this->assertSame('critical', $row['risk_level']);
    }

    public function test_material_forecast_gives_unknown_risk_without_consumption_data(): void
    {
        $item = Item::factory()->purchasedMaterial()->create(['item_number' => 'MAT-UNKNOWN']);
        StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => 10]);

        $row = collect(app(MaterialForecastService::class)->forecast()['rows'])->firstWhere('item_id', $item->id);

        $this->assertSame('unknown', $row['risk_level']);
    }

    public function test_supplier_performance_calculates_average_delivery_days(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Fast Steel']);
        $first = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id, 'ordered_at' => now()->subDays(10), 'expected_delivery_date' => now()->subDays(2)]);
        $second = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id, 'ordered_at' => now()->subDays(6), 'expected_delivery_date' => now()->addDay()]);
        GoodsReceipt::factory()->create(['purchase_order_id' => $first->id, 'received_at' => now()->subDays(5)]);
        GoodsReceipt::factory()->create(['purchase_order_id' => $second->id, 'received_at' => now()->subDays(2)]);

        $row = collect(app(SupplierPerformanceService::class)->analyze()['rows'])->firstWhere('supplier', 'Fast Steel');

        $this->assertSame(4.5, $row['average_delivery_days']);
    }

    public function test_quality_trend_calculates_reject_and_rework_rate(): void
    {
        $order = ProductionOrder::factory()->create(['order_number' => 'QUAL-MI-001']);
        $step = OperationSequenceStep::factory()->create(['operation_sequence_id' => $order->operation_sequence_id]);
        $accepted = ProductionTask::factory()->create(['production_order_id' => $order->id, 'operation_sequence_step_id' => $step->id]);
        $rework = ProductionTask::factory()->create(['production_order_id' => $order->id, 'operation_sequence_step_id' => $step->id]);
        $rejected = ProductionTask::factory()->create(['production_order_id' => $order->id, 'operation_sequence_step_id' => $step->id]);
        QualityCheck::factory()->create(['production_task_id' => $accepted->id, 'result' => QualityCheckResult::Accepted]);
        QualityCheck::factory()->create(['production_task_id' => $rework->id, 'result' => QualityCheckResult::ReworkRequired]);
        QualityCheck::factory()->create(['production_task_id' => $rejected->id, 'result' => QualityCheckResult::Rejected]);

        $row = collect(app(QualityTrendService::class)->analyze()['rows'])->firstWhere('production_order', 'QUAL-MI-001');

        $this->assertSame(1, $row['rework_count']);
        $this->assertSame(1, $row['rejected_count']);
        $this->assertSame(66.7, $row['defect_rate']);
    }

    public function test_production_risk_score_is_high_for_shortage_and_near_deadline(): void
    {
        $order = CustomerOrder::factory()->create([
            'order_number' => 'RISK-HIGH-001',
            'status' => CustomerOrderStatus::Confirmed,
            'requested_delivery_date' => now()->addDays(2)->toDateString(),
        ]);
        $orderItem = CustomerOrderItem::factory()->create(['customer_order_id' => $order->id]);
        MaterialRequirement::factory()->create(['customer_order_item_id' => $orderItem->id, 'missing_quantity' => 5]);
        ProductionOrder::factory()->create([
            'customer_order_item_id' => $orderItem->id,
            'planned_finish_date' => now()->subDay()->toDateString(),
            'status' => ProductionOrderStatus::Released,
        ]);

        $row = collect(app(ProductionRiskService::class)->score()['rows'])->firstWhere('customer_order', 'RISK-HIGH-001');

        $this->assertSame('high', $row['risk_level']);
    }

    public function test_procurement_recommendation_is_created_from_missing_material_requirement(): void
    {
        $item = $this->missingMaterialFixture();

        $row = collect(app(ProcurementRecommendationService::class)->recommendations()['rows'])->firstWhere('item_id', $item->id);

        $this->assertSame(12.0, $row['recommended_quantity']);
        $this->assertContains('CO-REC-001', $row['related_customer_orders']);
    }

    public function test_recommendations_do_not_create_purchase_requisitions(): void
    {
        $this->missingMaterialFixture();
        $before = PurchaseRequisition::query()->count();

        app(ProcurementRecommendationService::class)->recommendations();

        $this->assertSame($before, PurchaseRequisition::query()->count());
    }

    public function test_dashboard_cache_works(): void
    {
        $service = app(ManufacturingIntelligenceService::class);
        $first = $service->dashboard();
        CustomerOrder::factory()->create([
            'status' => CustomerOrderStatus::Confirmed,
            'requested_delivery_date' => now()->addDay()->toDateString(),
        ]);
        $second = $service->dashboard();

        $this->assertSame($first['high_risk_orders'], $second['high_risk_orders']);
        $this->assertTrue(Cache::has(BusinessCacheKey::make(BusinessCacheDomain::IntelligenceDashboard, 'summary')));
    }

    public function test_route_helper_contains_intelligence_routes(): void
    {
        $routes = file_get_contents(resource_path('js/Utils/routes.js'));

        $this->assertStringContainsString("'admin.intelligence.dashboard'", $routes);
        $this->assertStringContainsString("'admin.intelligence.recommendations'", $routes);
    }

    public function test_readonly_dashboard_opening_does_not_create_audit_log(): void
    {
        $before = Activity::query()->count();

        $this->actingAs($this->verifiedUser('viewer'))
            ->get(route('admin.intelligence.dashboard'))
            ->assertOk();

        $this->assertSame($before, Activity::query()->count());
    }

    private function verifiedUser(?string $role = null): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function missingMaterialFixture(): Item
    {
        $item = Item::factory()->purchasedMaterial()->create(['item_number' => 'REC-001']);
        $order = CustomerOrder::factory()->create(['order_number' => 'CO-REC-001']);
        $orderItem = CustomerOrderItem::factory()->create(['customer_order_id' => $order->id]);
        MaterialRequirement::factory()->create([
            'customer_order_item_id' => $orderItem->id,
            'required_item_id' => $item->id,
            'missing_quantity' => 12,
            'unit' => 'db',
        ]);

        return $item;
    }
}
