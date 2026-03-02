<?php

use App\Models\StaffTask;
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
        Schema::create('staff_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users', 'id')
                    ->nullOnDelete();
            $table->foreignId('manager_id')
                    ->nullable()
                    ->constrained('users', 'id')
                    ->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('status')->default(StaffTask::ACTIVE);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_tasks');
    }
};
