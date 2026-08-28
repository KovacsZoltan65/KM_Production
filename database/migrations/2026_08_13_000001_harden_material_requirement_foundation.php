<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_requirements', function (Blueprint $table): void {
            $table->foreignId('production_order_id')->nullable()->after('customer_order_item_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('bom_item_id')->nullable()->after('production_order_id')
                ->constrained()->restrictOnDelete();
            $table->date('required_at')->nullable()->after('required_item_id')->index();
            $table->index(
                ['production_order_id', 'bom_item_id'],
                'material_requirements_production_bom_index'
            );
            $table->index(
                ['production_order_id', 'required_item_id', 'required_at'],
                'material_requirements_demand_item_date_index'
            );
        });

        DB::table('material_requirements')
            ->orderBy('id')
            ->each(function (object $requirement): void {
                $candidates = DB::table('production_orders')
                    ->join('bom_items', function ($join) use ($requirement): void {
                        $join->on('bom_items.bom_id', '=', 'production_orders.bom_id')
                            ->where('bom_items.item_id', '=', $requirement->required_item_id);
                    })
                    ->join('production_plan_items', 'production_plan_items.id', '=', 'production_orders.production_plan_item_id')
                    ->join('production_plans', 'production_plans.id', '=', 'production_plan_items.production_plan_id')
                    ->join('customer_order_items', 'customer_order_items.id', '=', 'production_orders.customer_order_item_id')
                    ->join('customer_orders', 'customer_orders.id', '=', 'customer_order_items.customer_order_id')
                    ->where('production_orders.customer_order_item_id', $requirement->customer_order_item_id)
                    ->whereNull('production_orders.deleted_at')
                    ->get([
                        'production_orders.id as production_order_id',
                        'bom_items.id as bom_item_id',
                        'production_orders.planned_start_date',
                        'production_plan_items.planned_start_date as plan_item_start_date',
                        'production_plans.planned_start_date as plan_start_date',
                        'customer_orders.requested_delivery_date',
                    ]);

                // Ambiguous legacy rows remain explicitly un-attributed instead of inventing lineage.
                if ($candidates->count() !== 1) {
                    return;
                }

                $candidate = $candidates->first();
                DB::table('material_requirements')->where('id', $requirement->id)->update([
                    'production_order_id' => $candidate->production_order_id,
                    'bom_item_id' => $candidate->bom_item_id,
                    'required_at' => $candidate->planned_start_date
                        ?? $candidate->plan_item_start_date
                        ?? $candidate->plan_start_date
                        ?? $candidate->requested_delivery_date,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('material_requirements', function (Blueprint $table): void {
            $table->dropForeign(['bom_item_id']);
            $table->dropForeign(['production_order_id']);
        });

        Schema::table('material_requirements', function (Blueprint $table): void {
            $table->dropIndex('material_requirements_demand_item_date_index');
            $table->dropIndex('material_requirements_production_bom_index');
            $table->dropIndex('material_requirements_required_at_index');
        });

        Schema::table('material_requirements', function (Blueprint $table): void {
            $table->dropColumn(['bom_item_id', 'production_order_id', 'required_at']);
        });
    }
};
