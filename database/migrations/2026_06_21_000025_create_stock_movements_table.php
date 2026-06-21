<?php

use App\Enums\StockMovementType;
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
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_instance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('movement_type')->default(StockMovementType::Correction->value)->index();
            $table->nullableMorphs('source', 'stock_movements_source_index');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('item_id');
            $table->index('item_batch_id');
            $table->index('item_instance_id');
            $table->index('from_location_id');
            $table->index('to_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
