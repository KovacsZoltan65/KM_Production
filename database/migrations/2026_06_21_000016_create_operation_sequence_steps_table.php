<?php

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
        Schema::create('operation_sequence_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operation_sequence_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_order')->index();
            $table->foreignId('operation_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('factory_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('professional_role_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('estimated_duration_minutes');
            $table->boolean('requires_quality_check')->default(false)->index();
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->unique(['operation_sequence_id', 'step_order'], 'op_sequence_steps_order_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_sequence_steps');
    }
};
