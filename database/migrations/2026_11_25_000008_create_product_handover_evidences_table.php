<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_handover_evidences', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('item_instance_id');
            $table->foreignId('recipient_customer_id');
            $table->dateTime('handed_over_at', 6);
            $table->string('handover_reference', 128);
            $table->foreignId('evidence_document_id');
            $table->foreignId('verified_by');
            $table->dateTime('verified_at', 6);
            $table->foreignId('supersedes_evidence_id')->nullable();
            $table->foreignId('command_id');
            $table->dateTime('created_at', 6);
            $table->unique(['command_id'], 'rma_he_u0');
            $table->unique(['supersedes_evidence_id'], 'rma_he_u1');
            $table->unique(['id', 'item_instance_id'], 'rma_he_u2');
            $table->index(['item_instance_id', 'handed_over_at'], 'rma_he_i3');
            $table->index(['handover_reference'], 'rma_he_i4');
            $table->index(['recipient_customer_id'], 'rma_he_i5');
            $table->index(['evidence_document_id'], 'rma_he_i6');
            $table->index(['verified_by'], 'rma_he_i7');
            $table->foreign('item_instance_id', 'rma_he_f0')->references('id')->on('item_instances')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('recipient_customer_id', 'rma_he_f1')->references('id')->on('customers')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('evidence_document_id', 'rma_he_f2')->references('id')->on('documents')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('verified_by', 'rma_he_f3')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('supersedes_evidence_id', 'rma_he_f4')->references('id')->on('product_handover_evidences')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('command_id', 'rma_he_f5')->references('id')->on('repair_commands')->restrictOnUpdate()->restrictOnDelete();
        });

        // MySQL has native CHECKs; SQLite mirrors them with row guards.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product_handover_evidences ADD CONSTRAINT rma_he_c0 CHECK (verified_at>=handed_over_at)');
        } else {
            DB::unprepared("CREATE TRIGGER rma_he_c0_insert BEFORE INSERT ON product_handover_evidences WHEN NOT (NEW.verified_at>=NEW.handed_over_at) BEGIN SELECT RAISE(ABORT, 'rma_he_c0'); END");
            DB::unprepared("CREATE TRIGGER rma_he_c0_update BEFORE UPDATE ON product_handover_evidences WHEN NOT (NEW.verified_at>=NEW.handed_over_at) BEGIN SELECT RAISE(ABORT, 'rma_he_c0'); END");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_handover_evidences');
    }
};
