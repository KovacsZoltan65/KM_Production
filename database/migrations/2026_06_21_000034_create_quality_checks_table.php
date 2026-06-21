<?php

use App\Enums\QualityCheckResult;
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
        Schema::create('quality_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checked_by')->constrained('employees')->restrictOnDelete();
            $table->string('result')->default(QualityCheckResult::Accepted->value)->index();
            $table->timestamp('checked_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('production_task_id');
            $table->index('checked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_checks');
    }
};
