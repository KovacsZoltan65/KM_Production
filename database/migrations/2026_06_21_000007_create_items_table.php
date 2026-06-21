<?php

use App\Enums\ItemType;
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
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('item_number')->unique();
            $table->string('name');
            $table->string('item_type')->default(ItemType::PurchasedMaterial->value)->index();
            $table->string('unit');
            $table->decimal('width', 12, 3)->nullable();
            $table->decimal('length', 12, 3)->nullable();
            $table->decimal('thickness', 12, 3)->nullable();
            $table->decimal('diameter', 12, 3)->nullable();
            $table->boolean('requires_serial_number')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
