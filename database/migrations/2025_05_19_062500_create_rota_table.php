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
        Schema::create('rota_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('company_id')
                    ->constrained('companies', 'id')
                    ->cascadeOnDelete();
            $table->string('address');
            $table->timestamps();
        });

        Schema::create('rota_assigned_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')
                    ->constrained('rota_locations', 'id')
                    ->cascadeOnDelete();
            $table->foreignId('employee_id')
                    ->constrained('users', 'id')
                    ->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('rota_work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                    ->constrained('users', 'id')
                    ->cascadeOnDelete();
            $table->string('day'); // Saturday to Friday
            $table->integer('work_status'); // 1 => working, 0 => day_off
            $table->timestamps();
        });

        Schema::create('rota_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                    ->constrained('users', 'id')
                    ->cascadeOnDelete();
            $table->string('start_time');
            $table->string('end_time');
            $table->string('break'); // break in minutes
            $table->text('notes')->nullable();
            $table->boolean('published')->default(false);
            $table->boolean('sent_notification')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rota_locations');
        Schema::dropIfExists('rota_assigned_locations');
        Schema::dropIfExists('rota_work_schedules');
        Schema::dropIfExists('rota_shifts');
    }
};
