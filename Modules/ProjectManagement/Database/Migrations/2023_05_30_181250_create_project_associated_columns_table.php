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
        Schema::create('project_associated_columns', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('project_id')
                ->constrained('projects', 'id')
                ->cascadeOnDelete();
            $table->string('project_column_name');
            $table
                ->foreignId('created_by')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table->integer('column_position');
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
        Schema::dropIfExists('project_associated_columns');
    }
};
