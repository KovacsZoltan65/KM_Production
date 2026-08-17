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
            $table->foreignId('replenishment_item_supplier_id')->nullable()->after('replenishment_excess_quantity')
                ->constrained('item_suppliers')->restrictOnDelete();
            $table->decimal('replenishment_minimum_order_quantity', 18, 3)->nullable()->after('replenishment_item_supplier_id');
            $table->decimal('replenishment_order_multiple', 18, 3)->nullable()->after('replenishment_minimum_order_quantity');
            $table->string('replenishment_strategy', 40)->nullable()->after('replenishment_order_multiple');
            $table->timestamp('replenishment_calculated_at')->nullable()->after('replenishment_strategy');
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
            $table->dropForeign(['replenishment_item_supplier_id']);
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
