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
        Schema::create('factory_units', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->unsignedInteger('daily_capacity_minutes')->nullable();
            $table->unsignedTinyInteger('shift_count')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factory_units');
    }
};
