<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_assessments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('repair_order_id');
            $table->foreignId('item_instance_id');
            $table->foreignId('subject_item_id');
            $table->unsignedInteger('version');
            $table->string('classification', 24);
            $table->foreignId('handover_evidence_id')->nullable();
            $table->foreignId('product_warranty_rule_id')->nullable();
            $table->json('rule_snapshot');
            $table->dateTime('warranty_start', 6)->nullable();
            $table->dateTime('warranty_end', 6)->nullable();
            $table->foreignId('assessed_by');
            $table->dateTime('assessed_at', 6);
            $table->text('justification');
            $table->unsignedTinyInteger('is_override')->default(0);
            $table->text('override_reason')->nullable();
            $table->foreignId('command_id');
            $table->dateTime('created_at', 6);
            $table->unique(['repair_order_id', 'version'], 'rma_wa_u0');
            $table->unique(['command_id'], 'rma_wa_u1');
            $table->index(['item_instance_id', 'assessed_at'], 'rma_wa_i2');
            $table->index(['repair_order_id', 'item_instance_id', 'version'], 'rma_wa_i3');
            $table->index(['subject_item_id'], 'rma_wa_i4');
            $table->index(['handover_evidence_id'], 'rma_wa_i5');
            $table->index(['product_warranty_rule_id'], 'rma_wa_i6');
            $table->index(['assessed_by'], 'rma_wa_i7');
            $table->index(['item_instance_id', 'subject_item_id'], 'rma_wa_i8');
            $table->index(['handover_evidence_id', 'item_instance_id'], 'rma_wa_i9');
            $table->index(['product_warranty_rule_id', 'subject_item_id'], 'rma_wa_i10');
            $table->foreign('repair_order_id', 'rma_wa_f0')->references('id')->on('repair_orders')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('item_instance_id', 'rma_wa_f1')->references('id')->on('item_instances')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('subject_item_id', 'rma_wa_f2')->references('id')->on('items')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('handover_evidence_id', 'rma_wa_f3')->references('id')->on('product_handover_evidences')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('product_warranty_rule_id', 'rma_wa_f4')->references('id')->on('product_warranty_rules')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('assessed_by', 'rma_wa_f5')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('command_id', 'rma_wa_f6')->references('id')->on('repair_commands')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign(['item_instance_id', 'subject_item_id'], 'rma_wa_cf0')->references(['id', 'item_id'])->on('item_instances')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign(['handover_evidence_id', 'item_instance_id'], 'rma_wa_cf1')->references(['id', 'item_instance_id'])->on('product_handover_evidences')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign(['product_warranty_rule_id', 'subject_item_id'], 'rma_wa_cf2')->references(['id', 'item_id'])->on('product_warranty_rules')->restrictOnUpdate()->restrictOnDelete();
        });

        // MySQL has native CHECKs; SQLite mirrors them with row guards.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE warranty_assessments ADD CONSTRAINT rma_wa_c0 CHECK (version>=1)');
        } else {
            DB::unprepared("CREATE TRIGGER rma_wa_c0_insert BEFORE INSERT ON warranty_assessments WHEN NOT (NEW.version>=1) BEGIN SELECT RAISE(ABORT, 'rma_wa_c0'); END");
            DB::unprepared("CREATE TRIGGER rma_wa_c0_update BEFORE UPDATE ON warranty_assessments WHEN NOT (NEW.version>=1) BEGIN SELECT RAISE(ABORT, 'rma_wa_c0'); END");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE warranty_assessments ADD CONSTRAINT rma_wa_c1 CHECK (classification IN ('warranty','non_warranty','pending_assessment'))");
        } else {
            DB::unprepared("CREATE TRIGGER rma_wa_c1_insert BEFORE INSERT ON warranty_assessments WHEN NOT (NEW.classification IN ('warranty','non_warranty','pending_assessment')) BEGIN SELECT RAISE(ABORT, 'rma_wa_c1'); END");
            DB::unprepared("CREATE TRIGGER rma_wa_c1_update BEFORE UPDATE ON warranty_assessments WHEN NOT (NEW.classification IN ('warranty','non_warranty','pending_assessment')) BEGIN SELECT RAISE(ABORT, 'rma_wa_c1'); END");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE warranty_assessments ADD CONSTRAINT rma_wa_c2 CHECK (warranty_end IS NULL OR (warranty_start IS NOT NULL AND warranty_end>=warranty_start))');
        } else {
            DB::unprepared("CREATE TRIGGER rma_wa_c2_insert BEFORE INSERT ON warranty_assessments WHEN NOT (NEW.warranty_end IS NULL OR (NEW.warranty_start IS NOT NULL AND NEW.warranty_end>=NEW.warranty_start)) BEGIN SELECT RAISE(ABORT, 'rma_wa_c2'); END");
            DB::unprepared("CREATE TRIGGER rma_wa_c2_update BEFORE UPDATE ON warranty_assessments WHEN NOT (NEW.warranty_end IS NULL OR (NEW.warranty_start IS NOT NULL AND NEW.warranty_end>=NEW.warranty_start)) BEGIN SELECT RAISE(ABORT, 'rma_wa_c2'); END");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE warranty_assessments ADD CONSTRAINT rma_wa_c3 CHECK (is_override IN (0,1) AND ((is_override=0 AND override_reason IS NULL) OR (is_override=1 AND override_reason IS NOT NULL)))');
        } else {
            DB::unprepared("CREATE TRIGGER rma_wa_c3_insert BEFORE INSERT ON warranty_assessments WHEN NOT (NEW.is_override IN (0,1) AND ((NEW.is_override=0 AND NEW.override_reason IS NULL) OR (NEW.is_override=1 AND NEW.override_reason IS NOT NULL))) BEGIN SELECT RAISE(ABORT, 'rma_wa_c3'); END");
            DB::unprepared("CREATE TRIGGER rma_wa_c3_update BEFORE UPDATE ON warranty_assessments WHEN NOT (NEW.is_override IN (0,1) AND ((NEW.is_override=0 AND NEW.override_reason IS NULL) OR (NEW.is_override=1 AND NEW.override_reason IS NOT NULL))) BEGIN SELECT RAISE(ABORT, 'rma_wa_c3'); END");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_assessments');
    }
};
