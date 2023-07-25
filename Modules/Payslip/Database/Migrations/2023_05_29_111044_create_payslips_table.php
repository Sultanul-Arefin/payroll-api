<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
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
            $table->double('amount', 8, 2); // can store total 8 digits, 6 digits before decimal, & 2 after decimal
            $table->integer('email_flag')->default(Payslip::EMAIL_NOT_SENT);
            $table->integer('notification_flag')->default(Payslip::NOTIFICATION_NOT_SENT);
            $table->date('first_date');
            $table->date('last_date');
            $table->date('payment_date');
            $table->double('hours_worked', 6, 2); // can store total 6 digits, 4 digits before point, & 2 after point
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
