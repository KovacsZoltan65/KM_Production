<?php

use App\Enums\AiProcessingRunStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_processing_runs', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('processable', 'ai_processing_runs_processable_index');
            $table->string('task');
            $table->string('engine')->nullable();
            $table->string('engine_version')->nullable();
            $table->string('backend')->nullable();
            $table->string('status')->default(AiProcessingRunStatus::Pending->value);
            $table->boolean('success')->default(false);
            $table->decimal('confidence', 5, 4)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->json('result_summary')->nullable();
            $table->timestamps();

            $table->index(['task', 'status']);
            $table->index(['engine', 'backend']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_processing_runs');
    }
};
