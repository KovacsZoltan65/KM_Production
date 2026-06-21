<?php

namespace Database\Seeders;

use App\Enums\CustomerOrderItemStatus;
use App\Enums\CustomerOrderStatus;
use App\Enums\ProductionOrderStatus;
use App\Enums\ProductionPlanItemStatus;
use App\Enums\ProductionPlanStatus;
use App\Models\Bom;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\OperationSequence;
use App\Models\ProductionOrder;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use Illuminate\Database\Seeder;

class OrderProductionSeeder extends Seeder
{
    /**
     * Seed a sample order-to-production chain.
     */
    public function run(): void
    {
        $customer = Customer::query()->updateOrCreate(
            ['code' => 'TEST-CUSTOMER'],
            [
                'name' => 'Teszt vevő',
                'email' => 'customer@example.com',
                'is_active' => true,
            ],
        );

        $product = Item::query()->where('item_number', 'PRODUCT-AAA')->firstOrFail();
        $bom = Bom::query()
            ->where('item_id', $product->id)
            ->where('version', 1)
            ->firstOrFail();
        $operationSequence = OperationSequence::query()
            ->where('item_id', $product->id)
            ->where('version', 1)
            ->firstOrFail();

        $customerOrder = CustomerOrder::query()->updateOrCreate(
            ['order_number' => 'SO-2026-000001'],
            [
                'customer_id' => $customer->id,
                'status' => CustomerOrderStatus::Confirmed->value,
                'requested_delivery_date' => '2026-07-15',
                'confirmed_at' => now(),
                'notes' => 'Seedelt példa megrendelés.',
            ],
        );

        $customerOrderItem = CustomerOrderItem::query()->updateOrCreate(
            [
                'customer_order_id' => $customerOrder->id,
                'item_id' => $product->id,
            ],
            [
                'quantity' => 2,
                'unit' => 'db',
                'status' => CustomerOrderItemStatus::Planned->value,
                'notes' => 'PRODUCT-AAA rendelési tétel.',
            ],
        );

        $productionPlan = ProductionPlan::query()->updateOrCreate(
            ['plan_number' => 'PP-2026-000001'],
            [
                'customer_order_id' => $customerOrder->id,
                'status' => ProductionPlanStatus::Approved->value,
                'planned_start_date' => '2026-07-01',
                'planned_finish_date' => '2026-07-10',
                'approved_at' => now(),
                'notes' => 'Seedelt példa gyártási terv.',
            ],
        );

        $productionPlanItem = ProductionPlanItem::query()->updateOrCreate(
            [
                'production_plan_id' => $productionPlan->id,
                'customer_order_item_id' => $customerOrderItem->id,
            ],
            [
                'item_id' => $product->id,
                'quantity' => 2,
                'planned_start_date' => '2026-07-01',
                'planned_finish_date' => '2026-07-10',
                'status' => ProductionPlanItemStatus::Planned->value,
                'notes' => 'PRODUCT-AAA gyártási terv tétel.',
            ],
        );

        ProductionOrder::query()->updateOrCreate(
            ['order_number' => 'PO-2026-000001'],
            [
                'production_plan_item_id' => $productionPlanItem->id,
                'customer_order_item_id' => $customerOrderItem->id,
                'item_id' => $product->id,
                'bom_id' => $bom->id,
                'operation_sequence_id' => $operationSequence->id,
                'quantity' => 2,
                'status' => ProductionOrderStatus::Planned->value,
                'planned_start_date' => '2026-07-01',
                'planned_finish_date' => '2026-07-10',
                'notes' => 'Seedelt példa gyártási rendelés.',
            ],
        );
    }
}
