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
        Schema::create('dedicated_digital_tax_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->string('user_name');
            $table->string('country_name');
            $table->string('file_name');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dedicated_digital_tax_reports');
    }
};
