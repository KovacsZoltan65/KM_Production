<?php

namespace Database\Seeders;

use App\Enums\CustomerOrderItemStatus;
use App\Enums\CustomerOrderStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\ItemInstanceStatus;
use App\Enums\ItemType;
use App\Enums\LocationType;
use App\Enums\MaterialRequirementStatus;
use App\Enums\OperationTypeCode;
use App\Enums\ProductionTaskStatus;
use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionItemStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Enums\StockMovementType;
use App\Enums\StockReservationStatus;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Document;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\Location;
use App\Models\MaterialRequirement;
use App\Models\OperationType;
use App\Models\ProductionOrder;
use App\Models\ProductionPlan;
use App\Models\ProductionTask;
use App\Models\ProfessionalRole;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\QualityCheck;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        $this->resetTestOwnedData($admin, $restrictedUser);

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

        $professionalRole = ProfessionalRole::query()->updateOrCreate(
            ['code' => 'E2E-PRO'],
            [
                'name' => 'E2E Professional Role Before Partial Reload',
                'is_active' => true,
            ],
        );

        $operationType = OperationType::query()->updateOrCreate(
            ['code' => OperationTypeCode::PRODUCTION],
            [
                'name' => 'E2E Operation Type Before Partial Reload',
                'is_active' => true,
            ],
        );

        $refreshRole = Role::query()->create([
            'name' => 'e2e-refresh-role-before',
            'guard_name' => 'web',
        ]);
        $refreshPermission = Permission::query()->create([
            'name' => 'e2e-refresh-permission-before',
            'guard_name' => 'web',
        ]);

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
        $materialRequirementItem = Item::query()->updateOrCreate(
            ['item_number' => 'E2E-MR-001'],
            [
                'name' => 'E2E Material Requirement Item',
                'item_type' => ItemType::PurchasedMaterial,
                'unit' => 'db',
                'requires_serial_number' => false,
                'is_active' => true,
            ],
        );
        $reservationItem = Item::query()->updateOrCreate(
            ['item_number' => 'E2E-STOCK-RESERVATION-PARTIAL-REFRESH'],
            [
                'name' => 'E2E Stock Reservation Partial Refresh Item',
                'item_type' => ItemType::PurchasedMaterial,
                'unit' => 'db',
                'requires_serial_number' => false,
                'is_active' => true,
            ],
        );
        $stockMovementItem = Item::query()->updateOrCreate(
            ['item_number' => 'E2E-STOCK-MOVEMENT-PARTIAL-REFRESH'],
            [
                'name' => 'E2E Stock Movement Partial Refresh Item',
                'item_type' => ItemType::PurchasedMaterial,
                'unit' => 'db',
                'requires_serial_number' => false,
                'is_active' => true,
            ],
        );
        $stockMovementLocation = Location::query()->updateOrCreate(
            ['code' => 'E2E-SM-LOC'],
            [
                'factory_unit_id' => $factoryUnit->id,
                'name' => 'E2E Stock Movement Location',
                'location_type' => LocationType::Warehouse,
                'is_active' => true,
            ],
        );

        $stockMovement = StockMovement::query()->updateOrCreate(
            ['notes' => 'E2E-STOCK-MOVEMENT-PARTIAL-REFRESH-INITIAL'],
            [
                'item_id' => $stockMovementItem->id,
                'item_batch_id' => null,
                'item_instance_id' => null,
                'from_location_id' => null,
                'to_location_id' => $stockMovementLocation->id,
                'quantity' => 111.111,
                'movement_type' => StockMovementType::Correction,
                'source_type' => null,
                'source_id' => null,
                'performed_by' => $admin->id,
                'performed_at' => '2035-01-15 12:00:00',
            ],
        );
        $stockMovementBalance = StockBalance::query()->updateOrCreate(
            [
                'item_id' => $stockMovementItem->id,
                'location_id' => $stockMovementLocation->id,
                'item_batch_id' => null,
            ],
            ['quantity' => 500],
        );

        $reservation = StockReservation::withTrashed()
            ->firstOrNew(['notes' => 'E2E-STOCK-RESERVATION']);
        $reservation->fill([
            'item_id' => $reservationItem->id,
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

        StockBalance::query()->updateOrCreate(
            [
                'item_id' => $reservationItem->id,
                'location_id' => $location->id,
                'item_batch_id' => null,
            ],
            ['quantity' => 50],
        );

        $stockBalance = StockBalance::query()->updateOrCreate(
            [
                'item_id' => $item->id,
                'location_id' => $location->id,
                'item_batch_id' => null,
            ],
            ['quantity' => 12],
        );

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
        $supplier = Supplier::query()->updateOrCreate(
            ['code' => 'E2E-SUP'],
            [
                'name' => 'E2E Supplier Before Partial Reload',
                'email' => 'supplier-e2e@example.test',
                'is_active' => true,
            ],
        );
        $product = Item::query()->where('item_number', 'PRODUCT-AAA')->firstOrFail();

        $refreshPurchaseRequisition = PurchaseRequisition::query()->create([
            'requisition_number' => 'E2E-PR-REFRESH-001',
            'status' => PurchaseRequisitionStatus::Requested,
            'requested_by' => $admin->id,
            'requested_at' => '2035-01-15 12:00:00',
            'notes' => 'E2E purchase requisition partial refresh fixture.',
        ]);
        $refreshPurchaseRequisition->items()->create([
            'item_id' => $item->id,
            'quantity' => 11.111,
            'unit' => $item->unit,
            'status' => PurchaseRequisitionItemStatus::Requested,
        ]);

        $approvePurchaseRequisition = PurchaseRequisition::query()->create([
            'requisition_number' => 'E2E-PR-APPROVE-001',
            'status' => PurchaseRequisitionStatus::Requested,
            'requested_by' => $admin->id,
            'requested_at' => '2035-01-16 12:00:00',
            'notes' => 'E2E purchase requisition approve fixture.',
        ]);
        $approvePurchaseRequisition->items()->create([
            'item_id' => $item->id,
            'quantity' => 22.222,
            'unit' => $item->unit,
            'status' => PurchaseRequisitionItemStatus::Requested,
        ]);

        $generatePurchaseRequisition = PurchaseRequisition::query()->create([
            'requisition_number' => 'E2E-PR-GENERATE-001',
            'status' => PurchaseRequisitionStatus::Approved,
            'requested_by' => $admin->id,
            'requested_at' => '2035-01-17 12:00:00',
            'notes' => 'E2E purchase requisition PO generation fixture.',
        ]);
        $generatePurchaseRequisition->items()->create([
            'item_id' => $item->id,
            'quantity' => 33.333,
            'unit' => $item->unit,
            'status' => PurchaseRequisitionItemStatus::Requested,
        ]);

        $refreshPurchaseOrder = $this->createPurchaseOrderFixture(
            'E2E-PO-REFRESH-001',
            PurchaseOrderStatus::Ordered,
            $supplier,
            $item,
            $admin,
            '2036-01-15',
        );
        $approvePurchaseOrder = $this->createPurchaseOrderFixture(
            'E2E-PO-APPROVE-001',
            PurchaseOrderStatus::Draft,
            $supplier,
            $item,
            $admin,
        );
        $closePurchaseOrder = $this->createPurchaseOrderFixture(
            'E2E-PO-CLOSE-001',
            PurchaseOrderStatus::Ordered,
            $supplier,
            $item,
            $admin,
        );

        [$refreshGoodsReceipt] = $this->createGoodsReceiptFixture(
            'E2E-GR-REFRESH-001',
            'E2E-GR-PO-REFRESH-001',
            10,
            1,
            'E2E-GR-REFRESH-LOC',
            $supplier,
            $item,
            $admin,
            '2037-01-15 12:00:00',
        );
        [$partialGoodsReceipt, $partialGoodsReceiptPurchaseOrder, $partialGoodsReceiptItem, $partialGoodsReceiptLocation] = $this->createGoodsReceiptFixture(
            'E2E-GR-PARTIAL-POST-001',
            'E2E-GR-PO-PARTIAL-001',
            10,
            4,
            'E2E-GR-PARTIAL-LOC',
            $supplier,
            $item,
            $admin,
        );
        [$fullGoodsReceipt, $fullGoodsReceiptPurchaseOrder, $fullGoodsReceiptItem, $fullGoodsReceiptLocation] = $this->createGoodsReceiptFixture(
            'E2E-GR-FULL-POST-001',
            'E2E-GR-PO-FULL-001',
            6,
            6,
            'E2E-GR-FULL-LOC',
            $supplier,
            $item,
            $admin,
        );

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
        $customerOrderItem = CustomerOrderItem::query()->updateOrCreate(
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
        $shortage = MaterialRequirement::query()->updateOrCreate(
            ['notes' => 'E2E-SHORTAGE-PARTIAL-REFRESH'],
            [
                'customer_order_item_id' => $customerOrderItem->id,
                'required_item_id' => $item->id,
                'required_quantity' => 1000,
                'available_quantity' => 12.877,
                'reserved_quantity' => 0,
                'missing_quantity' => 987.123,
                'unit' => 'db',
                'status' => MaterialRequirementStatus::Missing,
            ],
        );
        $materialRequirementOrder = CustomerOrder::query()->updateOrCreate(
            ['order_number' => 'E2E-MR-SO-0001'],
            [
                'customer_id' => $customer->id,
                'status' => CustomerOrderStatus::Confirmed->value,
                'requested_delivery_date' => '2027-04-01',
                'confirmed_at' => now(),
                'notes' => 'E2E material requirement partial refresh order.',
            ],
        );
        $materialRequirementOrderItem = CustomerOrderItem::query()->updateOrCreate(
            [
                'customer_order_id' => $materialRequirementOrder->id,
                'item_id' => $product->id,
            ],
            [
                'quantity' => 1,
                'unit' => $product->unit,
                'status' => CustomerOrderItemStatus::Planned->value,
                'notes' => 'E2E material requirement partial refresh order item.',
            ],
        );
        $materialRequirement = MaterialRequirement::query()->updateOrCreate(
            ['notes' => 'E2E-MATERIAL-REQUIREMENT-PARTIAL-REFRESH'],
            [
                'customer_order_item_id' => $materialRequirementOrderItem->id,
                'required_item_id' => $materialRequirementItem->id,
                'required_quantity' => 321.123,
                'available_quantity' => 100,
                'reserved_quantity' => 20,
                'missing_quantity' => 221.123,
                'unit' => 'db',
                'status' => MaterialRequirementStatus::PartiallyAvailable,
            ],
        );
        $productionOrder = ProductionOrder::query()->where('order_number', 'PO-2026-000001')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-WELDER-001')->firstOrFail();
        $productionTask = ProductionTask::query()
            ->where('production_order_id', $productionOrder->id)
            ->firstOrFail();

        QualityCheck::query()->where('production_task_id', $productionTask->id)->delete();
        $productionTask->operationSequenceStep->update(['requires_quality_check' => true]);
        $productionTask->update([
            'status' => ProductionTaskStatus::Ready->value,
            'started_at' => null,
            'finished_at' => null,
        ]);
        $productionTask->itemInstance->update([
            'current_status' => ItemInstanceStatus::Planned->value,
        ]);

        $purchaseOrder = PurchaseOrder::query()->where('order_number', 'PO-SUP-2026-000001')->firstOrFail();

        File::ensureDirectoryExists(storage_path('framework/testing'));
        File::put(
            storage_path('framework/testing/e2e-fixtures.json'),
            json_encode([
                'itemId' => $item->id,
                'materialRequirementId' => $materialRequirement->id,
                'adminId' => $admin->id,
                'factoryUnitId' => $factoryUnit->id,
                'professionalRoleId' => $professionalRole->id,
                'operationTypeId' => $operationType->id,
                'roleId' => $refreshRole->id,
                'permissionId' => $refreshPermission->id,
                'reservationId' => $reservation->id,
                'stockMovementId' => $stockMovement->id,
                'stockMovementItemId' => $stockMovementItem->id,
                'stockMovementLocationId' => $stockMovementLocation->id,
                'stockMovementBalanceId' => $stockMovementBalance->id,
                'stockBalanceId' => $stockBalance->id,
                'shortageId' => $shortage->id,
                'customerId' => $customer->id,
                'supplierId' => $supplier->id,
                'productId' => $product->id,
                'customerOrderId' => $customerOrder->id,
                'productionOrderId' => $productionOrder->id,
                'productionTaskId' => $productionTask->id,
                'employeeId' => $employee->id,
                'purchaseOrderId' => $purchaseOrder->id,
                'refreshPurchaseRequisitionId' => $refreshPurchaseRequisition->id,
                'approvePurchaseRequisitionId' => $approvePurchaseRequisition->id,
                'generatePurchaseRequisitionId' => $generatePurchaseRequisition->id,
                'refreshPurchaseOrderId' => $refreshPurchaseOrder->id,
                'approvePurchaseOrderId' => $approvePurchaseOrder->id,
                'closePurchaseOrderId' => $closePurchaseOrder->id,
                'refreshGoodsReceiptId' => $refreshGoodsReceipt->id,
                'partialGoodsReceiptId' => $partialGoodsReceipt->id,
                'partialGoodsReceiptPurchaseOrderId' => $partialGoodsReceiptPurchaseOrder->id,
                'partialGoodsReceiptPurchaseOrderItemId' => $partialGoodsReceiptItem->id,
                'partialGoodsReceiptInventoryItemId' => $item->id,
                'partialGoodsReceiptLocationId' => $partialGoodsReceiptLocation->id,
                'fullGoodsReceiptId' => $fullGoodsReceipt->id,
                'fullGoodsReceiptPurchaseOrderId' => $fullGoodsReceiptPurchaseOrder->id,
                'fullGoodsReceiptPurchaseOrderItemId' => $fullGoodsReceiptItem->id,
                'fullGoodsReceiptInventoryItemId' => $item->id,
                'fullGoodsReceiptLocationId' => $fullGoodsReceiptLocation->id,
                'locationId' => $location->id,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );
    }

    private function resetTestOwnedData(User $admin, User $restrictedUser): void
    {
        DB::table('sessions')->delete();
        DB::table('activity_log')
            ->where('causer_type', User::class)
            ->whereIn('causer_id', [$admin->id, $restrictedUser->id])
            ->delete();

        Role::query()->where('name', 'like', 'e2e-refresh-role-%')->delete();
        Permission::query()->where('name', 'like', 'e2e-refresh-permission-%')->delete();

        StockMovement::query()
            ->where('notes', 'like', 'E2E-STOCK-MOVEMENT-PARTIAL-REFRESH-%')
            ->delete();

        $purchaseRequisitionIds = PurchaseRequisition::withTrashed()
            ->where('requisition_number', 'like', 'E2E-PR-%')
            ->pluck('id');

        if ($purchaseRequisitionIds->isNotEmpty()) {
            PurchaseOrder::withTrashed()
                ->whereIn('purchase_requisition_id', $purchaseRequisitionIds)
                ->forceDelete();
            PurchaseRequisition::withTrashed()
                ->whereIn('id', $purchaseRequisitionIds)
                ->forceDelete();
        }

        Item::withTrashed()
            ->where('item_number', 'like', 'E2E-NOTIFY-%')
            ->forceDelete();

        $testReceiptIds = GoodsReceipt::withTrashed()
            ->where('receipt_number', '!=', 'GR-2026-000001')
            ->pluck('id');

        if ($testReceiptIds->isNotEmpty()) {
            StockMovement::query()
                ->where('source_type', GoodsReceipt::class)
                ->whereIn('source_id', $testReceiptIds)
                ->delete();
            GoodsReceipt::withTrashed()
                ->whereIn('id', $testReceiptIds)
                ->forceDelete();
        }

        StockBalance::query()
            ->whereIn('location_id', Location::query()->where('code', 'like', 'E2E-GR-%')->select('id'))
            ->delete();

        PurchaseOrder::withTrashed()
            ->where('order_number', 'like', 'E2E-PO-%')
            ->forceDelete();
        PurchaseOrder::withTrashed()
            ->where('order_number', 'like', 'E2E-GR-PO-%')
            ->forceDelete();

        $this->call([
            InventorySeeder::class,
            ProcurementSeeder::class,
        ]);
    }

    private function createPurchaseOrderFixture(
        string $orderNumber,
        PurchaseOrderStatus $status,
        Supplier $supplier,
        Item $item,
        User $admin,
        ?string $expectedDeliveryDate = null,
    ): PurchaseOrder {
        $purchaseOrder = PurchaseOrder::query()->create([
            'order_number' => $orderNumber,
            'supplier_id' => $supplier->id,
            'status' => $status,
            'ordered_at' => $status === PurchaseOrderStatus::Draft ? null : '2036-01-01 12:00:00',
            'expected_delivery_date' => $expectedDeliveryDate,
            'notes' => 'E2E purchase order workflow fixture.',
            'created_by' => $admin->id,
        ]);
        $purchaseOrder->items()->create([
            'item_id' => $item->id,
            'ordered_quantity' => 44.444,
            'received_quantity' => 0,
            'unit' => $item->unit,
        ]);

        return $purchaseOrder;
    }

    /**
     * @return array{0: GoodsReceipt, 1: PurchaseOrder, 2: PurchaseOrderItem, 3: Location}
     */
    private function createGoodsReceiptFixture(
        string $receiptNumber,
        string $orderNumber,
        float $orderedQuantity,
        float $receiptQuantity,
        string $locationCode,
        Supplier $supplier,
        Item $item,
        User $admin,
        string $receivedAt = '2037-01-20 12:00:00',
    ): array {
        $location = Location::query()->updateOrCreate(
            ['code' => $locationCode],
            [
                'name' => $locationCode,
                'location_type' => LocationType::Warehouse,
                'is_active' => true,
            ],
        );
        $purchaseOrder = PurchaseOrder::query()->create([
            'order_number' => $orderNumber,
            'supplier_id' => $supplier->id,
            'status' => PurchaseOrderStatus::Ordered,
            'ordered_at' => '2037-01-01 12:00:00',
            'created_by' => $admin->id,
        ]);
        $purchaseOrderItem = $purchaseOrder->items()->create([
            'item_id' => $item->id,
            'ordered_quantity' => $orderedQuantity,
            'received_quantity' => 0,
            'unit' => $item->unit,
            'status' => PurchaseOrderItemStatus::Ordered,
        ]);
        $goodsReceipt = GoodsReceipt::query()->create([
            'receipt_number' => $receiptNumber,
            'purchase_order_id' => $purchaseOrder->id,
            'status' => GoodsReceiptStatus::Draft,
            'received_by' => $admin->id,
            'received_at' => $receivedAt,
            'notes' => 'E2E goods receipt workflow fixture.',
        ]);
        $goodsReceipt->items()->create([
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => $receiptQuantity,
        ]);

        return [$goodsReceipt, $purchaseOrder, $purchaseOrderItem, $location];
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
