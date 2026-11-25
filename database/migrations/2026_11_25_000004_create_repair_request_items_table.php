<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_request_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('repair_request_id');
            $table->foreignId('owner_customer_id');
            $table->foreignId('item_instance_id');
            $table->text('reported_fault');
            $table->foreignId('origin_production_order_id');
            $table->foreignId('origin_verified_by');
            $table->dateTime('origin_verified_at', 6);
            $table->json('origin_evidence');
            $table->foreignId('membership_command_id');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['repair_request_id', 'item_instance_id'], 'rma_rm_u0');
            $table->unique(['id', 'item_instance_id'], 'rma_rm_u1');
            $table->unique(['id', 'repair_request_id'], 'rma_rm_u2');
            $table->index(['item_instance_id', 'created_at'], 'rma_rm_i3');
            $table->index(['owner_customer_id'], 'rma_rm_i4');
            $table->index(['origin_production_order_id'], 'rma_rm_i5');
            $table->index(['origin_verified_by'], 'rma_rm_i6');
            $table->index(['membership_command_id'], 'rma_rm_i7');
            $table->index(['repair_request_id', 'owner_customer_id'], 'rma_rm_i8');
            $table->foreign('repair_request_id', 'rma_rm_f0')->references('id')->on('repair_requests')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('owner_customer_id', 'rma_rm_f1')->references('id')->on('customers')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('item_instance_id', 'rma_rm_f2')->references('id')->on('item_instances')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('origin_production_order_id', 'rma_rm_f3')->references('id')->on('production_orders')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('origin_verified_by', 'rma_rm_f4')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('membership_command_id', 'rma_rm_f5')->references('id')->on('repair_commands')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign(['repair_request_id', 'owner_customer_id'], 'rma_rm_cf0')->references(['id', 'owner_customer_id'])->on('repair_requests')->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_request_items');
    }
};
