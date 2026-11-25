<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_status_histories', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('repair_request_id')->nullable();
            $table->foreignId('repair_order_id')->nullable();
            $table->foreignId('repair_request_item_id')->nullable();
            $table->foreignId('item_instance_id')->nullable();
            $table->string('state_dimension', 32);
            $table->string('from_state', 32)->nullable();
            $table->string('to_state', 32);
            $table->foreignId('actor_id');
            $table->dateTime('occurred_at', 6);
            $table->text('reason');
            $table->json('metadata');
            $table->foreignId('authorization_document_id')->nullable();
            $table->foreignId('command_id');
            $table->unsignedInteger('event_sequence');
            $table->dateTime('created_at', 6);
            $table->unique(['command_id', 'event_sequence'], 'rma_sh_u0');
            $table->index(['repair_request_id', 'occurred_at'], 'rma_sh_i1');
            $table->index(['repair_order_id', 'state_dimension', 'occurred_at'], 'rma_sh_i2');
            $table->index(['repair_request_item_id'], 'rma_sh_i3');
            $table->index(['item_instance_id'], 'rma_sh_i4');
            $table->index(['actor_id'], 'rma_sh_i5');
            $table->index(['authorization_document_id'], 'rma_sh_i6');
            $table->index(['repair_request_item_id', 'item_instance_id'], 'rma_sh_i7');
            $table->foreign('repair_request_id', 'rma_sh_f0')->references('id')->on('repair_requests')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('repair_order_id', 'rma_sh_f1')->references('id')->on('repair_orders')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('repair_request_item_id', 'rma_sh_f2')->references('id')->on('repair_request_items')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('item_instance_id', 'rma_sh_f3')->references('id')->on('item_instances')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('actor_id', 'rma_sh_f4')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('authorization_document_id', 'rma_sh_f5')->references('id')->on('documents')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('command_id', 'rma_sh_f6')->references('id')->on('repair_commands')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign(['repair_request_item_id', 'item_instance_id'], 'rma_sh_cf0')->references(['id', 'item_instance_id'])->on('repair_request_items')->restrictOnUpdate()->restrictOnDelete();
        });

        // MySQL has native CHECKs; SQLite mirrors them with row guards.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE repair_status_histories ADD CONSTRAINT rma_sh_c0 CHECK ((repair_request_id IS NOT NULL AND repair_order_id IS NULL) OR (repair_request_id IS NULL AND repair_order_id IS NOT NULL))');
        } else {
            DB::unprepared("CREATE TRIGGER rma_sh_c0_insert BEFORE INSERT ON repair_status_histories WHEN NOT ((NEW.repair_request_id IS NOT NULL AND NEW.repair_order_id IS NULL) OR (NEW.repair_request_id IS NULL AND NEW.repair_order_id IS NOT NULL)) BEGIN SELECT RAISE(ABORT, 'rma_sh_c0'); END");
            DB::unprepared("CREATE TRIGGER rma_sh_c0_update BEFORE UPDATE ON repair_status_histories WHEN NOT ((NEW.repair_request_id IS NOT NULL AND NEW.repair_order_id IS NULL) OR (NEW.repair_request_id IS NULL AND NEW.repair_order_id IS NOT NULL)) BEGIN SELECT RAISE(ABORT, 'rma_sh_c0'); END");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE repair_status_histories ADD CONSTRAINT rma_sh_c1 CHECK ((repair_request_item_id IS NULL AND item_instance_id IS NULL) OR (repair_request_item_id IS NOT NULL AND item_instance_id IS NOT NULL))');
        } else {
            DB::unprepared("CREATE TRIGGER rma_sh_c1_insert BEFORE INSERT ON repair_status_histories WHEN NOT ((NEW.repair_request_item_id IS NULL AND NEW.item_instance_id IS NULL) OR (NEW.repair_request_item_id IS NOT NULL AND NEW.item_instance_id IS NOT NULL)) BEGIN SELECT RAISE(ABORT, 'rma_sh_c1'); END");
            DB::unprepared("CREATE TRIGGER rma_sh_c1_update BEFORE UPDATE ON repair_status_histories WHEN NOT ((NEW.repair_request_item_id IS NULL AND NEW.item_instance_id IS NULL) OR (NEW.repair_request_item_id IS NOT NULL AND NEW.item_instance_id IS NOT NULL)) BEGIN SELECT RAISE(ABORT, 'rma_sh_c1'); END");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE repair_status_histories ADD CONSTRAINT rma_sh_c2 CHECK (event_sequence>=1)');
        } else {
            DB::unprepared("CREATE TRIGGER rma_sh_c2_insert BEFORE INSERT ON repair_status_histories WHEN NOT (NEW.event_sequence>=1) BEGIN SELECT RAISE(ABORT, 'rma_sh_c2'); END");
            DB::unprepared("CREATE TRIGGER rma_sh_c2_update BEFORE UPDATE ON repair_status_histories WHEN NOT (NEW.event_sequence>=1) BEGIN SELECT RAISE(ABORT, 'rma_sh_c2'); END");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE repair_status_histories ADD CONSTRAINT rma_sh_c3 CHECK (state_dimension IN ('case','claim','warranty'))");
        } else {
            DB::unprepared("CREATE TRIGGER rma_sh_c3_insert BEFORE INSERT ON repair_status_histories WHEN NOT (NEW.state_dimension IN ('case','claim','warranty')) BEGIN SELECT RAISE(ABORT, 'rma_sh_c3'); END");
            DB::unprepared("CREATE TRIGGER rma_sh_c3_update BEFORE UPDATE ON repair_status_histories WHEN NOT (NEW.state_dimension IN ('case','claim','warranty')) BEGIN SELECT RAISE(ABORT, 'rma_sh_c3'); END");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_status_histories');
    }
};
