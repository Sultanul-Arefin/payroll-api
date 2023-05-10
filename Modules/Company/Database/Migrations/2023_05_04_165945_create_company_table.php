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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_address');
            $table->string('company_email');
            $table->string('company_phone');
            $table->string('company_logo');
            $table->string('company_website')->nullable();
            $table->string('company_registration_no')->nullable();
            $table->string('government_employee_no')->nullable();
            $table->date('fiscal_year_from');
            $table->date('fiscal_year_to');
            $table->string('bank_name')->nullable();
            $table->string('bank_bic_or_swift_code')->nullable();
            $table->string('bank_iban_or_account_no')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_email')->nullable();
            $table->string('contact_person_phone')->nullable();
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
