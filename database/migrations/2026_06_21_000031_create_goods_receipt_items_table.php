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
        Schema::create('goods_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('goods_receipt_id');
            $table->index('purchase_order_item_id');
            $table->index('item_id');
            $table->index('item_batch_id');
            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
