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
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['item_id', 'location_id', 'item_batch_id'], 'stock_balances_unique');
            $table->index('item_id');
            $table->index('location_id');
            $table->index('item_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
