<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_commands', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->binary('command_type', 64);
            $table->binary('idempotency_key', 128);
            $table->binary('payload_hash', 32, true);
            $table->foreignId('actor_id');
            $table->string('status', 16)->default('processing');
            $table->binary('result_type', 64)->nullable();
            $table->unsignedBigInteger('result_id')->nullable();
            $table->json('result_snapshot')->nullable();
            $table->dateTime('completed_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['command_type', 'idempotency_key'], 'rma_rc_u0');
            $table->index(['actor_id', 'created_at'], 'rma_rc_i1');
            $table->foreign('actor_id', 'rma_rc_f0')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();
        });

        // MySQL has native CHECKs; SQLite mirrors them with row guards.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE repair_commands ADD CONSTRAINT rma_rc_c0 CHECK (status IN ('processing','completed'))");
        } else {
            DB::unprepared("CREATE TRIGGER rma_rc_c0_insert BEFORE INSERT ON repair_commands WHEN NOT (NEW.status IN ('processing','completed')) BEGIN SELECT RAISE(ABORT, 'rma_rc_c0'); END");
            DB::unprepared("CREATE TRIGGER rma_rc_c0_update BEFORE UPDATE ON repair_commands WHEN NOT (NEW.status IN ('processing','completed')) BEGIN SELECT RAISE(ABORT, 'rma_rc_c0'); END");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE repair_commands ADD CONSTRAINT rma_rc_c1 CHECK ((status='processing' AND completed_at IS NULL) OR (status='completed' AND completed_at IS NOT NULL AND result_snapshot IS NOT NULL))");
        } else {
            DB::unprepared("CREATE TRIGGER rma_rc_c1_insert BEFORE INSERT ON repair_commands WHEN NOT ((NEW.status='processing' AND NEW.completed_at IS NULL) OR (NEW.status='completed' AND NEW.completed_at IS NOT NULL AND NEW.result_snapshot IS NOT NULL)) BEGIN SELECT RAISE(ABORT, 'rma_rc_c1'); END");
            DB::unprepared("CREATE TRIGGER rma_rc_c1_update BEFORE UPDATE ON repair_commands WHEN NOT ((NEW.status='processing' AND NEW.completed_at IS NULL) OR (NEW.status='completed' AND NEW.completed_at IS NOT NULL AND NEW.result_snapshot IS NOT NULL)) BEGIN SELECT RAISE(ABORT, 'rma_rc_c1'); END");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_commands');
    }
};
