<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_repair_claims', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->foreignId('item_instance_id');
            $table->foreignId('repair_request_item_id');
            $table->foreignId('claimed_by');
            $table->foreignId('command_id');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->primary('item_instance_id');
            $table->unique(['repair_request_item_id'], 'rma_cl_u0');
            $table->index(['claimed_by'], 'rma_cl_i1');
            $table->index(['command_id'], 'rma_cl_i2');
            $table->index(['repair_request_item_id', 'item_instance_id'], 'rma_cl_i3');
            $table->foreign('item_instance_id', 'rma_cl_f0')->references('id')->on('item_instances')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('repair_request_item_id', 'rma_cl_f1')->references('id')->on('repair_request_items')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('claimed_by', 'rma_cl_f2')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('command_id', 'rma_cl_f3')->references('id')->on('repair_commands')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign(['repair_request_item_id', 'item_instance_id'], 'rma_cl_cf0')->references(['id', 'item_instance_id'])->on('repair_request_items')->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_repair_claims');
    }
};
