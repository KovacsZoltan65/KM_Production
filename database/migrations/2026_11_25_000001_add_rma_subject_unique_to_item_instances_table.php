<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_instances', function (Blueprint $table): void {
            $table->unique(['id', 'item_id'], 'rma_item_instance_subject_u');
        });
    }

    public function down(): void
    {
        Schema::table('item_instances', function (Blueprint $table): void {
            $table->dropUnique('rma_item_instance_subject_u');
        });
    }
};
