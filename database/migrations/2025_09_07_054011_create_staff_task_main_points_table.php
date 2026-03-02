<?php

use App\Models\StaffTaskMainPoint;
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
        Schema::create('staff_task_main_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_task_id')
                    ->constrained('staff_tasks', 'id')
                    ->cascadeOnDelete();
            $table->text('title')->nullable();
            $table->foreignId('manager_id')
                    ->nullable()
                    ->constrained('users', 'id')
                    ->nullOnDelete();
            $table->foreignId('assigned_to')
                    ->nullable()
                    ->constrained('users', 'id')
                    ->nullOnDelete();
            $table->date('given_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('progress')->default(StaffTaskMainPoint::PENDING);
            $table->string('documents')->nullable();
            $table->double('estimated_time')->nullable();
            $table->integer('signed_off_by_manager')->default(StaffTaskMainPoint::NOT_SINGED_OFF);
            $table->timestamp('signed_off_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_task_main_points');
    }
};
