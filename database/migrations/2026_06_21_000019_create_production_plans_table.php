<?php

use App\Enums\ProductionPlanStatus;
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
        Schema::create('production_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_order_id')->constrained()->restrictOnDelete();
            $table->string('plan_number')->unique();
            $table->string('status')->default(ProductionPlanStatus::Draft->value)->index();
            $table->date('planned_start_date')->nullable()->index();
            $table->date('planned_finish_date')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plans');
    }
};
