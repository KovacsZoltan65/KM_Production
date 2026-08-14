<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_requirement_pegs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_balance_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('unit', 20);
            $table->date('supply_at')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['material_requirement_id', 'stock_balance_id'], 'mr_peg_requirement_stock_unique');
            $table->unique(['material_requirement_id', 'purchase_order_item_id'], 'mr_peg_requirement_po_item_unique');
            $table->index(['material_requirement_id', 'supply_at'], 'mr_peg_requirement_supply_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requirement_pegs');
    }
};
