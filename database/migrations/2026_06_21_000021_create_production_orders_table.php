<?php

use App\Enums\ProductionOrderStatus;
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
        Schema::create('production_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_plan_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('bom_id')->constrained()->restrictOnDelete();
            $table->foreignId('operation_sequence_id')->constrained()->restrictOnDelete();
            $table->string('order_number')->unique();
            $table->decimal('quantity', 12, 3);
            $table->string('status')->default(ProductionOrderStatus::Planned->value)->index();
            $table->date('planned_start_date')->nullable()->index();
            $table->date('planned_finish_date')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('production_plan_item_id');
            $table->index('customer_order_item_id');
            $table->index('item_id');
            $table->index('bom_id');
            $table->index('operation_sequence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
