<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_orders', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('repair_request_item_id');
            $table->string('work_order_number', 64);
            $table->string('execution_status', 32)->default('awaiting_receipt');
            $table->unsignedInteger('current_attempt_number')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['repair_request_item_id'], 'rma_ro_u0');
            $table->unique(['work_order_number'], 'rma_ro_u1');
            $table->index(['execution_status', 'created_at'], 'rma_ro_i2');
            $table->foreign('repair_request_item_id', 'rma_ro_f0')->references('id')->on('repair_request_items')->restrictOnUpdate()->restrictOnDelete();
        });

        // MySQL has native CHECKs; SQLite mirrors them with row guards.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE repair_orders ADD CONSTRAINT rma_ro_c0 CHECK (execution_status='awaiting_receipt' AND current_attempt_number=1)");
        } else {
            DB::unprepared("CREATE TRIGGER rma_ro_c0_insert BEFORE INSERT ON repair_orders WHEN NOT (NEW.execution_status='awaiting_receipt' AND NEW.current_attempt_number=1) BEGIN SELECT RAISE(ABORT, 'rma_ro_c0'); END");
            DB::unprepared("CREATE TRIGGER rma_ro_c0_update BEFORE UPDATE ON repair_orders WHEN NOT (NEW.execution_status='awaiting_receipt' AND NEW.current_attempt_number=1) BEGIN SELECT RAISE(ABORT, 'rma_ro_c0'); END");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_orders');
    }
};
