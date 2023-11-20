<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_associated_employees', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('project_id')
                ->constrained('projects', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('assigned_employees')
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
        Schema::dropIfExists('project_associated_employees');
    }
};
