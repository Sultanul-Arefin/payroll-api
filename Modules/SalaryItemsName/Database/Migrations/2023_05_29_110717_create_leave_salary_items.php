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
        Schema::create('leave_salary_items', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('salary_items_id')
                ->constrained('salary_items_names', 'id')
                ->cascadeOnDelete();
            $table->integer('no_of_days')->default(0);
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
        Schema::dropIfExists('leave_salary_items');
    }
};
