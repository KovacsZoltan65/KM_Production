<?php

use App\Enums\ItemInstanceStatus;
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
        Schema::create('item_instances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('serial_number')->unique();
            $table->foreignId('factory_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('current_status')->default(ItemInstanceStatus::Planned->value)->index();
            $table->unsignedBigInteger('production_order_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_instances');
    }
};
