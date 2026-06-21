<?php

use App\Enums\ProductionPlanItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_plan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->date('planned_start_date')->nullable()->index();
            $table->date('planned_finish_date')->nullable()->index();
            $table->string('status')->default(ProductionPlanItemStatus::Draft->value)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('production_plan_id');
            $table->index('customer_order_item_id');
            $table->index('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plan_items');
    }
};
