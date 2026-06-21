<?php

use App\Enums\MaterialRequirementStatus;
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
        Schema::create('material_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('required_item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('required_quantity', 12, 3);
            $table->decimal('available_quantity', 12, 3)->default(0);
            $table->decimal('reserved_quantity', 12, 3)->default(0);
            $table->decimal('missing_quantity', 12, 3)->default(0);
            $table->string('unit');
            $table->string('status')->default(MaterialRequirementStatus::Calculated->value)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_order_item_id');
            $table->index('required_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_requirements');
    }
};
