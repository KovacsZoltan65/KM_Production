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
        Schema::create('item_instance_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_item_instance_id')->constrained('item_instances')->cascadeOnDelete();
            $table->foreignId('child_item_instance_id')->constrained('item_instances')->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->timestamps();

            $table->unique(
                ['parent_item_instance_id', 'child_item_instance_id'],
                'item_instance_relation_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_instance_relations');
    }
};
