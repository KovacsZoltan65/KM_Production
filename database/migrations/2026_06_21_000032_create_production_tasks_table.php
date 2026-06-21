<?php

use App\Enums\ProductionTaskStatus;
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
        Schema::create('production_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operation_sequence_step_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('status')->default(ProductionTaskStatus::Planned->value)->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['item_instance_id', 'operation_sequence_step_id'], 'production_tasks_instance_step_unique');
            $table->index('production_order_id');
            $table->index('item_instance_id');
            $table->index('operation_sequence_step_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_tasks');
    }
};
