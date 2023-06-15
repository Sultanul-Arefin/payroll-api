<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('project_associated_column_id')
                ->constrained('project_associated_columns', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('project_id')
                ->constrained('projects', 'id')
                ->cascadeOnDelete();
            $table->string('task_title');
            $table->text('task_description')->nullable();
            $table->string('estimation_hour')->nullable();
            $table->date('start_date_time')->nullable();
            $table->date('end_date_time')->nullable();
            $table
                ->foreignId('created_by')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
