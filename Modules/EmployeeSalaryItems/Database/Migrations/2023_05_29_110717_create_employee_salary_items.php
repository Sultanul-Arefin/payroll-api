<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_salary_items', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('salary_item_id')
                ->constrained('salary_items_names', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('employee_id')
                ->nullable()
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('company_id')
                ->constrained('companies', 'id')
                ->cascadeOnDelete();
            $table->integer('is_percentage')->default(EmployeeSalaryItem::IS_AMOUNT);
            $table->integer('is_general')->default(EmployeeSalaryItem::IS_NOT_GENERAL);
            // $table->integer('amount')->nullable();
            $table->float('amount', 6, 2)->nullable();
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
        Schema::dropIfExists('employee_salary_items');
    }
};
