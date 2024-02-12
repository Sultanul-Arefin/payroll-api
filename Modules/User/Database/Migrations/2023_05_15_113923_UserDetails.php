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
            $table->string('nid')->nullable();
            $table->string('fax')->nullable();
            $table->string('passport')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_bic_or_swift_code')->nullable();
            $table->string('bank_iban_or_account_no')->nullable();
            $table->string('tin')->nullable();
            $table->string('user_image')->nullable();
            $table->integer('payslip_type')->default(UserDetails::UNIVERSAL_PAYSLIP);
            $table->integer('attendance_type')->default(UserDetails::WEB_ATTENDANCE);
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
