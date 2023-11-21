<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Company\Entities\Company;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_address')->nullable();
            $table->string('company_email');
            $table->string('company_phone');
            $table->string('company_logo')->nullable();
            $table->string('company_website')->nullable();
            $table->string('status')->default(Company::PENDING);
            $table->string('company_registration_no')->nullable();
            $table->string('government_employee_no')->nullable();
            $table->date('fiscal_year_from')->nullable();
            $table->date('fiscal_year_to')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_bic_or_swift_code')->nullable();
            $table->string('bank_iban_or_account_no')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_email')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->decimal('no_of_working_days_per_week', 4, 2)->nullable();
            $table->decimal('working_hours_per_day', 4, 2)->nullable();
            $table->decimal('lunch_and_others_per_day', 4, 2)->nullable();
            $table->decimal('working_hours_per_week', 4, 2)->nullable();
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
        Schema::dropIfExists('companies');
    }
};
