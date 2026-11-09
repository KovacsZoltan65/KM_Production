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
        Schema::create('production_task_materials', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('A gyártási feladatokhoz tervezett és ténylegesen felhasznált anyagmennyiségek.');

            $table->id();
            $table->foreignId('production_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('planned_quantity', 12, 3);
            $table->decimal('used_quantity', 12, 3);
            $table->string('unit');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('production_task_id');
            $table->index('item_id');
            $table->index('item_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_task_materials');
    }
};
