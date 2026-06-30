<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\OperationSequence;
use App\Models\ProductionOrder;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use Database\Seeders\ItemMasterDataSeeder;
use Database\Seeders\OrderProductionSeeder;
use Database\Seeders\ProductionMasterDataSeeder;
use Database\Seeders\ProductionStructureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_order_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $order = CustomerOrder::factory()->create(['customer_id' => $customer->id]);

        $this->assertTrue($order->customer->is($customer));
        $this->assertTrue($customer->customerOrders->contains($order));
    }

    public function test_customer_order_item_belongs_to_order_and_item(): void
    {
        $order = CustomerOrder::factory()->create();
        $item = Item::factory()->finishedProduct()->create();

        $orderItem = CustomerOrderItem::factory()->create([
            'customer_order_id' => $order->id,
            'item_id' => $item->id,
        ]);

        $this->assertTrue($orderItem->customerOrder->is($order));
        $this->assertTrue($orderItem->item->is($item));
        $this->assertTrue($order->items->contains($orderItem));
    }

    public function test_customer_order_number_must_be_unique(): void
    {
        CustomerOrder::factory()->create(['order_number' => 'SO-2026-000001']);

        $this->expectException(QueryException::class);

        CustomerOrder::factory()->create(['order_number' => 'SO-2026-000001']);
    }

    public function test_production_plan_belongs_to_customer_order(): void
    {
        $order = CustomerOrder::factory()->create();
        $plan = ProductionPlan::factory()->create(['customer_order_id' => $order->id]);

        $this->assertTrue($plan->customerOrder->is($order));
        $this->assertTrue($order->productionPlans->contains($plan));
    }

    public function test_production_plan_item_belongs_to_customer_order_item(): void
    {
        $orderItem = CustomerOrderItem::factory()->create();
        $plan = ProductionPlan::factory()->create(['customer_order_id' => $orderItem->customer_order_id]);

        $planItem = ProductionPlanItem::factory()->create([
            'production_plan_id' => $plan->id,
            'customer_order_item_id' => $orderItem->id,
            'item_id' => $orderItem->item_id,
        ]);

        $this->assertTrue($planItem->customerOrderItem->is($orderItem));
        $this->assertTrue($plan->items->contains($planItem));
    }

    public function test_production_order_belongs_to_production_plan_item(): void
    {
        $context = $this->productionContext();

        $productionOrder = ProductionOrder::factory()->create($this->productionOrderAttributes($context));

        $this->assertTrue($productionOrder->productionPlanItem->is($context['production_plan_item']));
        $this->assertTrue($context['production_plan_item']->productionOrders->contains($productionOrder));
    }

    public function test_production_order_belongs_to_customer_order_item(): void
    {
        $context = $this->productionContext();

        $productionOrder = ProductionOrder::factory()->create($this->productionOrderAttributes($context));

        $this->assertTrue($productionOrder->customerOrderItem->is($context['customer_order_item']));
        $this->assertTrue($context['customer_order_item']->productionOrders->contains($productionOrder));
    }

    public function test_production_order_records_bom_version(): void
    {
        $context = $this->productionContext();

        $productionOrder = ProductionOrder::factory()->create($this->productionOrderAttributes($context));

        $this->assertTrue($productionOrder->bom->is($context['bom']));
        $this->assertSame(1, $productionOrder->bom->version);
    }

    public function test_production_order_records_operation_sequence_version(): void
    {
        $context = $this->productionContext();

        $productionOrder = ProductionOrder::factory()->create($this->productionOrderAttributes($context));

        $this->assertTrue($productionOrder->operationSequence->is($context['operation_sequence']));
        $this->assertSame(1, $productionOrder->operationSequence->version);
    }

    public function test_production_order_number_must_be_unique(): void
    {
        $context = $this->productionContext();

        ProductionOrder::factory()->create([
            ...$this->productionOrderAttributes($context),
            'order_number' => 'PO-2026-000001',
        ]);

        $this->expectException(QueryException::class);

        ProductionOrder::factory()->create([
            ...$this->productionOrderAttributes($context),
            'order_number' => 'PO-2026-000001',
        ]);
    }

    public function test_order_production_seeder_is_idempotent(): void
    {
        $this->seed(ProductionMasterDataSeeder::class);
        $this->seed(ItemMasterDataSeeder::class);
        $this->seed(ProductionStructureSeeder::class);
        $this->seed(OrderProductionSeeder::class);
        $this->seed(OrderProductionSeeder::class);

        $this->assertDatabaseCount('customer_orders', 1);
        $this->assertDatabaseCount('customer_order_items', 1);
        $this->assertDatabaseCount('production_plans', 1);
        $this->assertDatabaseCount('production_plan_items', 1);
        $this->assertDatabaseCount('production_orders', 1);

        $this->assertDatabaseHas('production_orders', [
            'order_number' => 'PO-2026-000001',
            'quantity' => 2,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productionContext(): array
    {
        $item = Item::factory()->finishedProduct()->create();
        $customerOrderItem = CustomerOrderItem::factory()->create(['item_id' => $item->id]);
        $productionPlan = ProductionPlan::factory()->create([
            'customer_order_id' => $customerOrderItem->customerOrder->id,
        ]);
        $productionPlanItem = ProductionPlanItem::factory()->create([
            'production_plan_id' => $productionPlan->id,
            'customer_order_item_id' => $customerOrderItem->id,
            'item_id' => $item->id,
        ]);
        $bom = Bom::factory()->create([
            'item_id' => $item->id,
            'version' => 1,
        ]);
        $operationSequence = OperationSequence::factory()->create([
            'item_id' => $item->id,
            'version' => 1,
        ]);

        return [
            'production_plan_item_id' => $productionPlanItem->id,
            'customer_order_item_id' => $customerOrderItem->id,
            'item_id' => $item->id,
            'bom_id' => $bom->id,
            'operation_sequence_id' => $operationSequence->id,
            'quantity' => $customerOrderItem->quantity,
            'production_plan_item' => $productionPlanItem,
            'customer_order_item' => $customerOrderItem,
            'bom' => $bom,
            'operation_sequence' => $operationSequence,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function productionOrderAttributes(array $context): array
    {
        return [
            'production_plan_item_id' => $context['production_plan_item_id'],
            'customer_order_item_id' => $context['customer_order_item_id'],
            'item_id' => $context['item_id'],
            'bom_id' => $context['bom_id'],
            'operation_sequence_id' => $context['operation_sequence_id'],
            'quantity' => $context['quantity'],
        ];
    }
}
