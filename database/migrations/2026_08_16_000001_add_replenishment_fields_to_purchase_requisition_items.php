<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table): void {
            $table->decimal('planned_quantity', 18, 3)->nullable()->after('quantity');
            $table->decimal('replenishment_excess_quantity', 18, 3)->default(0)->after('planned_quantity');
            $table->foreignId('replenishment_item_supplier_id')->nullable()->after('replenishment_excess_quantity');
            $table->decimal('replenishment_minimum_order_quantity', 18, 3)->nullable()->after('replenishment_item_supplier_id');
            $table->decimal('replenishment_order_multiple', 18, 3)->nullable()->after('replenishment_minimum_order_quantity');
            $table->string('replenishment_strategy', 40)->nullable()->after('replenishment_order_multiple');
            $table->timestamp('replenishment_calculated_at')->nullable()->after('replenishment_strategy');

            $table->foreign('replenishment_item_supplier_id', 'pr_item_replenishment_supplier_fk')
                ->references('id')
                ->on('item_suppliers')
                ->restrictOnDelete();
        });

        DB::table('purchase_requisition_items')->update([
            'planned_quantity' => DB::raw('quantity'),
        ]);

        Schema::table('purchase_requisition_items', function (Blueprint $table): void {
            $table->decimal('planned_quantity', 18, 3)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table): void {
            if (DB::getDriverName() === 'sqlite') {
                $table->dropForeign(['replenishment_item_supplier_id']);
            } else {
                $table->dropForeign('pr_item_replenishment_supplier_fk');
            }
        });

        Schema::table('purchase_requisition_items', function (Blueprint $table): void {
            $table->dropColumn([
                'planned_quantity',
                'replenishment_excess_quantity',
                'replenishment_item_supplier_id',
                'replenishment_minimum_order_quantity',
                'replenishment_order_multiple',
                'replenishment_strategy',
                'replenishment_calculated_at',
            ]);
        });
    }
};
