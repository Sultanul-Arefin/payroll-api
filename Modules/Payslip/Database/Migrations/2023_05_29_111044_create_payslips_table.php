<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Payslip\Entities\Payslip;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('employee_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('company_id')
                ->constrained('companies', 'id')
                ->cascadeOnDelete();
            $table->string('month');
            $table->integer('email_flag')->default(Payslip::EMAIL_NOT_SENT);
            $table->integer('notification_flag')->default(Payslip::NOTIFICATION_NOT_SENT);
            $table->integer('pay_frequency')->default(Payslip::PAY_FREQUENCY_MONTHLY);
            $table->date('first_date');
            $table->date('last_date');
            $table->date('payment_date');
            $table->double('hours_worked', 6, 2)->nullable(); // can store total 6 digits, 4 digits before point, & 2 after point
            // $table->double('amount', 8, 2); // can store total 8 digits, 6 digits before decimal, & 2 after decimal
            $table->double('wages', 8, 2); // wages_val -> basic/basic rate calculation with hour
            $table->double('additional_pay', 8, 2)->default(0.00); // bonus/overtime/double overtime
            $table->double('leave_deduction', 8, 2)->default(0.00); // deduction_val -> leave related deduction calculation
            $table->double('total_pay_value', 8, 2); // total_pay_val -> wages - leave_deduction
            $table->double('taxable_allowance', 8, 2)->default(0.00); // allowance_val -> taxable allowance
            $table->double('gross_pay_before_tax', 8, 2); // gross_pay_before_tax
            $table->double('tax_value', 8, 2)->default(0.00); // tax_val -> income tax
            $table->double('post_tax_value', 8, 2)->default(0.00); // post_tax_val -> other/top-up tax
            $table->double('gross_pay_after_tax', 8, 2); // gross_pay_after_tax
            $table->double('non_taxable_allowance', 8, 2)->default(0.00); // non_tax_val -> non taxable allowance
            $table->double('pay_deduction', 8, 2)->default(0.00); // pay_deduction -> non pay_deduction allowance
            $table->double('pay_due_before_deduction', 8, 2)->default(0.00); // pay_due_before_deduction -> pay due before deduction
            $table->double('company_contribution_value', 8, 2)->default(0.00); // category => 7, for company
            $table->double('employee_contribution_value', 8, 2)->default(0.00); // category => 7, for employee
            $table->double('other_company_deduction', 8, 2)->default(0.00); // category => 8, for employee
            $table->double('other_company_contribution', 8, 2)->default(0.00); // category => 8, for company
            $table->double('net_pay', 8, 2)->default(0.00); // net_pay -> net pay before company & government contribution -> main salary
            $table->double('total_employee_deduction', 8, 2)->default(0.00); // employee_contribution_value + other_company_deduction
            $table->double('company_contribution', 8, 2)->default(0.00); // company_contribution_value + other_company_contribution
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
        Schema::dropIfExists('payslips');
    }
};
