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
        Schema::create('project_columns', function (Blueprint $table) {
            $table->id();
            $table->string('column_name');
            $table
                ->foreignId('company_id')
                ->constrained('companies', 'id')
                ->cascadeOnDelete();
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
        Schema::dropIfExists('project_columns');
    }
};
