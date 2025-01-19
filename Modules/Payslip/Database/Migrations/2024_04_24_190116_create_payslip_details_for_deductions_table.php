<?php

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
        Schema::create('payslip_details_for_deductions', function (Blueprint $table) {
            $table->id();
            $table->uuid('payslip_id');
            $table
                ->foreign('payslip_id')
                ->references('id')
                ->on('payslips')
                ->cascadeOnDelete();
            $table
                ->foreignId('salary_item_id')
                ->constrained('salary_items_names', 'id')
                ->cascadeOnDelete();
            $table->double('employee_amount', 8, 2)->default(0.00);
            $table->double('government_or_company_amount', 8, 2)->default(0.00);
            $table->string('employee_amount_rate')->default(0);
            $table->string('government_or_company_amount_rate')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_details_for_deductions');
    }
};
