<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_suppliers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('supplier_item_code')->nullable();
            $table->string('purchase_unit', 50);
            $table->decimal('conversion_factor', 18, 6)->default(1);
            $table->decimal('minimum_order_quantity', 18, 3)->nullable();
            $table->decimal('order_multiple', 18, 3)->nullable();
            $table->decimal('unit_price', 18, 4)->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'supplier_id'], 'item_suppliers_item_supplier_unique');
            $table->index(['item_id', 'is_active', 'is_approved'], 'item_suppliers_item_eligibility_index');
            $table->index(['supplier_id', 'is_active'], 'item_suppliers_supplier_active_index');
            $table->index(['item_id', 'is_preferred'], 'item_suppliers_item_preferred_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_suppliers');
    }
};
