<?php

use App\Enums\CustomerOrderStatus;
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
        Schema::create('customer_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status')->default(CustomerOrderStatus::Draft->value)->index();
            $table->date('requested_delivery_date')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
