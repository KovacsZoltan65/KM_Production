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
        Schema::create('serial_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('prefix')->index();
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['prefix', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_sequences');
    }
};
