<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\User\Entities\UserDetails;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table->string('user_area')->nullable();
            $table->string('user_city')->nullable();
            $table->string('zip_code')->nullable();
            $table->foreignId('country_id')
                ->constrained('countries', 'id')
                ->cascadeOnDelete();
            $table->string('user_phone');
            $table->tinyInteger('gender')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('social_security_number')->nullable();
            $table->string('pension_number')->nullable();
            $table->string('visa_number')->nullable();
            $table->string('work_permit_number')->nullable();
            $table->string('uan_no')->nullable();
            $table->string('pf_no')->nullable();
            $table->string('esi_no')->nullable();
            $table->string('ni_category')->nullable();
            $table->string('national_identity_number')->nullable();
            $table->string('national_insurance_number')->nullable();
            $table->json('others_number')->nullable();
            $table->string('fax')->nullable();
            $table->string('passport')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_bic_or_swift_code')->nullable();
            $table->string('bank_iban_or_account_no')->nullable();
            $table->string('user_image')->nullable();
            $table->integer('payslip_type')->default(UserDetails::UNIVERSAL_PAYSLIP);
            $table->integer('attendance_type')->default(UserDetails::WEB_ATTENDANCE);
            $table->string('state')->nullable();
            $table->string('region')->nullable();
            $table->string('religion')->nullable();
            $table->string('health_insurance')->nullable();
            $table->string('pension_administrator')->nullable();
            $table->string('dependent')->nullable();
            $table->string('pension_pin')->nullable();
            $table->string('tin_number')->nullable();
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
        Schema::dropIfExists('user_details');
    }
};
