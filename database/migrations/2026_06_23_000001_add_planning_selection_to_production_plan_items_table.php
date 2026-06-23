<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plan_items', function (Blueprint $table): void {
            $table->foreignId('bom_id')
                ->nullable()
                ->after('item_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('operation_sequence_id')
                ->nullable()
                ->after('bom_id')
                ->constrained()
                ->nullOnDelete();

            $table->index('bom_id');
            $table->index('operation_sequence_id');
        });
    }

    public function down(): void
    {
        Schema::table('production_plan_items', function (Blueprint $table): void {
            $table->dropForeign(['bom_id']);
            $table->dropForeign(['operation_sequence_id']);
            $table->dropIndex(['bom_id']);
            $table->dropIndex(['operation_sequence_id']);
            $table->dropColumn(['bom_id', 'operation_sequence_id']);
        });
    }
};
