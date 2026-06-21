<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('item_id');
            $table->unique(['item_id', 'version'], 'boms_item_version_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boms');
    }
};
