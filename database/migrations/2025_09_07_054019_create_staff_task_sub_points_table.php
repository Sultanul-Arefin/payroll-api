<?php

use App\Models\StaffTaskSubPoint;
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
        Schema::create('staff_task_sub_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_task_main_point_id')
                    ->constrained('staff_task_main_points', 'id')
                    ->cascadeOnDelete();
            $table->text('title');
            $table->foreignId('manager_id')
                    ->nullable()
                    ->constrained('users', 'id')
                    ->nullOnDelete();
            $table->foreignId('assigned_to')
                    ->nullable()
                    ->constrained('users', 'id')
                    ->nullOnDelete();
            $table->date('given_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('progress')->default(StaffTaskSubPoint::PENDING);
            $table->string('documents')->nullable();
            $table->double('estimated_time')->nullable();
            $table->integer('signed_off_by_manager')->default(StaffTaskSubPoint::NOT_SINGED_OFF);
            $table->timestamp('signed_off_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_task_sub_points');
    }
};
