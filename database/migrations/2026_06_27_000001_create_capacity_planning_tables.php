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
        Schema::create('factory_unit_calendars', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('factory_unit_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->index();
            $table->time('work_start');
            $table->time('work_end');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->boolean('is_working_day')->default(true)->index();
            $table->timestamps();

            $table->unique(['factory_unit_id', 'weekday']);
        });

        Schema::create('employee_work_calendars', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->index();
            $table->time('work_start');
            $table->time('work_end');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'weekday']);
        });

        Schema::create('capacity_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('factory_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reserved_from')->index();
            $table->timestamp('reserved_until')->index();
            $table->unsignedInteger('planned_minutes');
            $table->string('status')->default('planned')->index();
            $table->timestamps();

            $table->unique('production_task_id');
            $table->index(['factory_unit_id', 'reserved_from', 'reserved_until'], 'capacity_reservations_unit_time_index');
            $table->index(['employee_id', 'reserved_from', 'reserved_until'], 'capacity_reservations_employee_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capacity_reservations');
        Schema::dropIfExists('employee_work_calendars');
        Schema::dropIfExists('factory_unit_calendars');
    }
};
