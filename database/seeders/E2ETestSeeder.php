<?php

namespace Database\Seeders;

use App\Enums\ItemInstanceStatus;
use App\Enums\ItemType;
use App\Enums\LocationType;
use App\Enums\CustomerOrderItemStatus;
use App\Enums\CustomerOrderStatus;
use App\Enums\ProductionTaskStatus;
use App\Enums\StockReservationStatus;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Document;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\Location;
use App\Models\ProductionOrder;
use App\Models\ProductionPlan;
use App\Models\ProductionTask;
use App\Models\PurchaseOrder;
use App\Models\QualityCheck;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Spatie\Permission\Models\Permission;

class E2ETestSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'e2e-admin@example.test';

    public const RESTRICTED_EMAIL = 'e2e-inventory-viewer@example.test';

    public const PASSWORD = 'E2E-Only-Password!';

    public function run(): void
    {
        $this->assertSafeEnvironment();
        $this->seedBaselineDataWhenMissing();

        $admin = User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'E2E Admin',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['super-admin']);

        $restrictedUser = User::query()->updateOrCreate(
            ['email' => self::RESTRICTED_EMAIL],
            [
                'name' => 'E2E Inventory Viewer',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ],
        );
        $restrictedUser->syncRoles([]);
        $restrictedUser->syncPermissions(['inventory.view']);

        $factoryUnit = FactoryUnit::query()->updateOrCreate(
            ['code' => 'E2E-FU'],
            [
                'name' => 'E2E Factory Unit',
                'daily_capacity_minutes' => 480,
                'shift_count' => 1,
                'is_active' => true,
            ],
        );

        $location = Location::query()->updateOrCreate(
            ['code' => 'E2E-LOC'],
            [
                'factory_unit_id' => $factoryUnit->id,
                'name' => 'E2E Warehouse',
                'location_type' => LocationType::Warehouse,
                'is_active' => true,
            ],
        );

        $item = Item::query()->updateOrCreate(
            ['item_number' => 'E2E-MAT-001'],
            [
                'name' => 'E2E Test Material',
                'item_type' => ItemType::PurchasedMaterial,
                'unit' => 'db',
                'requires_serial_number' => false,
                'is_active' => true,
            ],
        );

        $reservation = StockReservation::withTrashed()
            ->firstOrNew(['notes' => 'E2E-STOCK-RESERVATION']);
        $reservation->fill([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'item_batch_id' => null,
            'customer_order_item_id' => null,
            'production_order_id' => null,
            'reserved_quantity' => 12.5,
            'status' => StockReservationStatus::Active,
            'reserved_by' => $admin->id,
            'reserved_at' => now(),
            'released_at' => null,
        ]);
        $reservation->save();
        if ($reservation->trashed()) {
            $reservation->restore();
        }

        Document::withTrashed()
            ->where('documentable_type', Item::class)
            ->where('documentable_id', $item->id)
            ->forceDelete();
        Storage::disk('e2e')->deleteDirectory('documents');

        $customer = Customer::query()->updateOrCreate(
            ['code' => 'E2E-CUST'],
            [
                'name' => 'E2E Customer',
                'email' => 'customer-e2e@example.test',
                'is_active' => true,
            ],
        );
        $product = Item::query()->where('item_number', 'PRODUCT-AAA')->firstOrFail();

        CustomerOrder::withTrashed()
            ->where('notes', 'E2E customer order UI workflow')
            ->forceDelete();
        ProductionPlan::withTrashed()
            ->where('notes', 'E2E production plan UI workflow')
            ->forceDelete();

        $customerOrder = CustomerOrder::query()->updateOrCreate(
            ['order_number' => 'E2E-SO-0001'],
            [
                'customer_id' => $customer->id,
                'status' => CustomerOrderStatus::Confirmed->value,
                'requested_delivery_date' => '2027-02-01',
                'confirmed_at' => now(),
                'notes' => 'E2E seed customer order for production plan tests.',
            ],
        );
        CustomerOrderItem::query()->updateOrCreate(
            [
                'customer_order_id' => $customerOrder->id,
                'item_id' => $product->id,
            ],
            [
                'quantity' => 3,
                'unit' => $product->unit,
                'status' => CustomerOrderItemStatus::Planned->value,
                'notes' => 'E2E seed order item.',
            ],
        );
        $productionOrder = ProductionOrder::query()->where('order_number', 'PO-2026-000001')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-WELDER-001')->firstOrFail();
        $productionTask = ProductionTask::query()
            ->where('production_order_id', $productionOrder->id)
            ->firstOrFail();

        QualityCheck::query()->where('production_task_id', $productionTask->id)->delete();
        $productionTask->operationSequenceStep?->update(['requires_quality_check' => true]);
        $productionTask->update([
            'status' => ProductionTaskStatus::Ready->value,
            'started_at' => null,
            'finished_at' => null,
        ]);
        $productionTask->itemInstance?->update([
            'current_status' => ItemInstanceStatus::Planned->value,
        ]);

        $purchaseOrder = PurchaseOrder::query()->where('order_number', 'PO-SUP-2026-000001')->firstOrFail();

        File::ensureDirectoryExists(storage_path('framework/testing'));
        File::put(
            storage_path('framework/testing/e2e-fixtures.json'),
            json_encode([
                'itemId' => $item->id,
                'reservationId' => $reservation->id,
                'customerId' => $customer->id,
                'productId' => $product->id,
                'customerOrderId' => $customerOrder->id,
                'productionOrderId' => $productionOrder->id,
                'productionTaskId' => $productionTask->id,
                'employeeId' => $employee->id,
                'purchaseOrderId' => $purchaseOrder->id,
                'locationId' => $location->id,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );
    }

    private function assertSafeEnvironment(): void
    {
        $configuredDatabase = (string) config('database.connections.sqlite.database');
        $expectedDatabase = database_path('e2e.sqlite');
        $normalize = static fn (string $path): string => str_replace('\\', '/', $path);

        if (! app()->environment('e2e')) {
            throw new LogicException('The E2E seeder may only run with APP_ENV=e2e.');
        }

        if (config('database.default') !== 'sqlite') {
            throw new LogicException('The E2E seeder requires the SQLite connection.');
        }

        if ($normalize($configuredDatabase) !== $normalize($expectedDatabase)) {
            throw new LogicException('The E2E seeder may only use database/e2e.sqlite.');
        }

        if (config('filesystems.default') !== 'e2e') {
            throw new LogicException('The E2E seeder requires FILESYSTEM_DISK=e2e.');
        }

        if (config('queue.default') !== 'sync') {
            throw new LogicException('The E2E seeder requires QUEUE_CONNECTION=sync.');
        }
    }

    private function seedBaselineDataWhenMissing(): void
    {
        if (! Permission::query()->where('name', 'customer-orders.view')->exists()) {
            $this->call(RolesAndPermissionsSeeder::class);
        }

        if (! Item::query()->where('item_number', 'PRODUCT-AAA')->exists()) {
            $this->call([
                ProductionMasterDataSeeder::class,
                ItemMasterDataSeeder::class,
                ProductionStructureSeeder::class,
                OrderProductionSeeder::class,
                InventorySeeder::class,
                ProcurementSeeder::class,
                ProductionExecutionSeeder::class,
            ]);
        }
    }
}
