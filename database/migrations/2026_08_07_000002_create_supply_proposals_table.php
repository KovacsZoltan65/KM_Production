<?php

use App\Enums\SupplyProposalStatus;
use App\Enums\SupplyStrategy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_proposals', function (Blueprint $table): void {
            $table->id();
            $table->string('strategy', 30)->default(SupplyStrategy::Purchase->value);
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('proposed_quantity', 18, 3);
            $table->string('unit', 50);
            $table->date('required_at')->nullable();
            $table->date('proposed_supply_at')->nullable();
            $table->string('status', 30)->default(SupplyProposalStatus::Draft->value);
            $table->string('reason_code', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'strategy']);
            $table->index(['item_id', 'status']);
            $table->index(['supplier_id', 'status']);
            $table->index('required_at');
            $table->index('proposed_supply_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_proposals');
    }
};
