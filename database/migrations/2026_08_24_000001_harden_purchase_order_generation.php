<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsNamedForeignKeyRollback = DB::getDriverName() !== 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->string('supplier_code_snapshot')->nullable()->after('supplier_id');
            $table->string('supplier_name_snapshot')->nullable()->after('supplier_code_snapshot');
            $table->unique('purchase_requisition_id', 'purchase_orders_requisition_unique');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) use ($supportsNamedForeignKeyRollback): void {
            $table->foreignId('item_supplier_id')->nullable()->after('purchase_requisition_item_id');
            $table->string('item_number_snapshot')->nullable()->after('item_id');
            $table->string('item_name_snapshot')->nullable()->after('item_number_snapshot');
            $table->decimal('planned_quantity_snapshot', 18, 3)->nullable()->after('ordered_quantity');
            $table->decimal('replenishment_excess_quantity_snapshot', 18, 3)->nullable()->after('planned_quantity_snapshot');
            $table->string('purchase_unit_snapshot')->nullable()->after('unit');
            $table->decimal('conversion_factor_snapshot', 18, 6)->nullable()->after('purchase_unit_snapshot');
            $table->decimal('unit_price_snapshot', 18, 4)->nullable()->after('conversion_factor_snapshot');
            $table->string('currency_snapshot', 3)->nullable()->after('unit_price_snapshot');
            $table->unsignedInteger('lead_time_days_snapshot')->nullable()->after('currency_snapshot');
            $table->decimal('minimum_order_quantity_snapshot', 18, 3)->nullable()->after('lead_time_days_snapshot');
            $table->decimal('order_multiple_snapshot', 18, 3)->nullable()->after('minimum_order_quantity_snapshot');

            if ($supportsNamedForeignKeyRollback) {
                $table->foreign('item_supplier_id', 'purchase_order_items_item_supplier_fk')
                    ->references('id')
                    ->on('item_suppliers')
                    ->restrictOnDelete();
            }
            $table->index('item_supplier_id');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('purchase_order_items', function (Blueprint $table): void {
                $table->dropForeign('purchase_order_items_item_supplier_fk');
            });
        }

        Schema::table('purchase_order_items', function (Blueprint $table): void {
            $table->dropIndex(['item_supplier_id']);
            $table->dropColumn([
                'item_supplier_id',
                'item_number_snapshot',
                'item_name_snapshot',
                'planned_quantity_snapshot',
                'replenishment_excess_quantity_snapshot',
                'purchase_unit_snapshot',
                'conversion_factor_snapshot',
                'unit_price_snapshot',
                'currency_snapshot',
                'lead_time_days_snapshot',
                'minimum_order_quantity_snapshot',
                'order_multiple_snapshot',
            ]);
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropUnique('purchase_orders_requisition_unique');
            $table->dropColumn(['supplier_code_snapshot', 'supplier_name_snapshot']);
        });
    }
};
