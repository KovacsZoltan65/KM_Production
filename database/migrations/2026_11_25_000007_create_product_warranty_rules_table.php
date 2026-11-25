<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_warranty_rules', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('item_id');
            $table->unsignedInteger('version');
            $table->dateTime('effective_from', 6);
            $table->unsignedInteger('duration_value');
            $table->string('duration_unit', 8);
            $table->text('rule_reference');
            $table->foreignId('approved_by');
            $table->dateTime('approved_at', 6);
            $table->foreignId('command_id');
            $table->dateTime('created_at', 6);
            $table->unique(['item_id', 'version'], 'rma_wr_u0');
            $table->unique(['item_id', 'effective_from'], 'rma_wr_u1');
            $table->unique(['id', 'item_id'], 'rma_wr_u2');
            $table->index(['approved_by'], 'rma_wr_i3');
            $table->index(['command_id'], 'rma_wr_i4');
            $table->foreign('item_id', 'rma_wr_f0')->references('id')->on('items')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('approved_by', 'rma_wr_f1')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('command_id', 'rma_wr_f2')->references('id')->on('repair_commands')->restrictOnUpdate()->restrictOnDelete();
        });

        // MySQL has native CHECKs; SQLite mirrors them with row guards.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product_warranty_rules ADD CONSTRAINT rma_wr_c0 CHECK (version>=1 AND duration_value>0)');
        } else {
            DB::unprepared("CREATE TRIGGER rma_wr_c0_insert BEFORE INSERT ON product_warranty_rules WHEN NOT (NEW.version>=1 AND NEW.duration_value>0) BEGIN SELECT RAISE(ABORT, 'rma_wr_c0'); END");
            DB::unprepared("CREATE TRIGGER rma_wr_c0_update BEFORE UPDATE ON product_warranty_rules WHEN NOT (NEW.version>=1 AND NEW.duration_value>0) BEGIN SELECT RAISE(ABORT, 'rma_wr_c0'); END");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE product_warranty_rules ADD CONSTRAINT rma_wr_c1 CHECK (duration_unit IN ('day','month','year'))");
        } else {
            DB::unprepared("CREATE TRIGGER rma_wr_c1_insert BEFORE INSERT ON product_warranty_rules WHEN NOT (NEW.duration_unit IN ('day','month','year')) BEGIN SELECT RAISE(ABORT, 'rma_wr_c1'); END");
            DB::unprepared("CREATE TRIGGER rma_wr_c1_update BEFORE UPDATE ON product_warranty_rules WHEN NOT (NEW.duration_unit IN ('day','month','year')) BEGIN SELECT RAISE(ABORT, 'rma_wr_c1'); END");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_warranty_rules');
    }
};
