<?php

use App\Enums\StockReservationStatus;
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
        Schema::create('stock_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_order_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('production_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('reserved_quantity', 12, 3);
            $table->string('status')->default(StockReservationStatus::Active->value)->index();
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reserved_at')->nullable()->index();
            $table->timestamp('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('item_id');
            $table->index('location_id');
            $table->index('item_batch_id');
            $table->index('customer_order_item_id');
            $table->index('production_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
