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
        Schema::create('deduction_details', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('employee_salary_item_id')
                ->constrained('employee_salary_items', 'id')
                ->cascadeOnDelete();
            $table->integer('employee_amount')->nullable();
            $table->integer('government_or_company_amount')->nullable();
            $table->integer('start_percentage')->nullable();
            $table->integer('end_percentage')->nullable();
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
        Schema::dropIfExists('deduction_details');
    }
};
