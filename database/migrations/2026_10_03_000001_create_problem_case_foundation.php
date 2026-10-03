<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problem_cases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 40);
            $table->foreignId('material_requirement_id')->constrained()->restrictOnDelete();
            $table->string('lifecycle', 30);
            $table->json('detection_snapshot');
            $table->string('current_evaluation', 30);
            $table->timestamp('current_evaluated_at');
            $table->json('current_evaluation_evidence');
            $table->timestamps();

            $table->index(['material_requirement_id', 'lifecycle']);
        });

        Schema::create('problem_case_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('problem_case_id')->constrained()->restrictOnDelete();
            $table->string('result', 30);
            $table->timestamp('evaluated_at');
            $table->string('recorded_for', 100);
            $table->json('evidence');
            $table->timestamp('created_at');

            $table->index(['problem_case_id', 'evaluated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problem_case_evaluations');
        Schema::dropIfExists('problem_cases');
    }
};
