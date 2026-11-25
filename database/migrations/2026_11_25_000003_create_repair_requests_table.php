<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_requests', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('case_number', 64);
            $table->foreignId('owner_customer_id');
            $table->foreignId('created_by');
            $table->foreignId('creator_employee_id');
            $table->foreignId('reporter_employee_id')->nullable();
            $table->string('reporter_name', 255);
            $table->json('reporter_contact_snapshot');
            $table->json('owner_snapshot');
            $table->string('status', 16)->default('open');
            $table->dateTime('closed_at', 6)->nullable();
            $table->foreignId('closed_by')->nullable();
            $table->foreignId('opening_command_id');
            $table->foreignId('closing_command_id')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['case_number'], 'rma_rq_u0');
            $table->unique(['opening_command_id'], 'rma_rq_u1');
            $table->unique(['closing_command_id'], 'rma_rq_u2');
            $table->unique(['id', 'owner_customer_id'], 'rma_rq_u3');
            $table->index(['owner_customer_id', 'created_at'], 'rma_rq_i4');
            $table->index(['status', 'created_at'], 'rma_rq_i5');
            $table->index(['created_by'], 'rma_rq_i6');
            $table->index(['creator_employee_id'], 'rma_rq_i7');
            $table->index(['reporter_employee_id'], 'rma_rq_i8');
            $table->index(['closed_by'], 'rma_rq_i9');
            $table->foreign('owner_customer_id', 'rma_rq_f0')->references('id')->on('customers')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('created_by', 'rma_rq_f1')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('creator_employee_id', 'rma_rq_f2')->references('id')->on('employees')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('reporter_employee_id', 'rma_rq_f3')->references('id')->on('employees')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('closed_by', 'rma_rq_f4')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('opening_command_id', 'rma_rq_f5')->references('id')->on('repair_commands')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('closing_command_id', 'rma_rq_f6')->references('id')->on('repair_commands')->restrictOnUpdate()->restrictOnDelete();
        });

        // MySQL has native CHECKs; SQLite mirrors them with row guards.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE repair_requests ADD CONSTRAINT rma_rq_c0 CHECK (status='open' AND closed_at IS NULL AND closed_by IS NULL AND closing_command_id IS NULL)");
        } else {
            DB::unprepared("CREATE TRIGGER rma_rq_c0_insert BEFORE INSERT ON repair_requests WHEN NOT (NEW.status='open' AND NEW.closed_at IS NULL AND NEW.closed_by IS NULL AND NEW.closing_command_id IS NULL) BEGIN SELECT RAISE(ABORT, 'rma_rq_c0'); END");
            DB::unprepared("CREATE TRIGGER rma_rq_c0_update BEFORE UPDATE ON repair_requests WHEN NOT (NEW.status='open' AND NEW.closed_at IS NULL AND NEW.closed_by IS NULL AND NEW.closing_command_id IS NULL) BEGIN SELECT RAISE(ABORT, 'rma_rq_c0'); END");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};
