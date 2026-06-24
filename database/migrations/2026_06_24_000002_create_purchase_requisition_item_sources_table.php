<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requisition_item_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('purchase_requisition_item_id');
            $table->unsignedBigInteger('material_requirement_id');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->unique(['purchase_requisition_item_id', 'material_requirement_id'], 'pr_item_source_unique');
            $table->index('material_requirement_id');
            $table->foreign('purchase_requisition_item_id', 'pr_item_source_item_fk')
                ->references('id')
                ->on('purchase_requisition_items')
                ->cascadeOnDelete();
            $table->foreign('material_requirement_id', 'pr_item_source_requirement_fk')
                ->references('id')
                ->on('material_requirements')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_item_sources');
    }
};
