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
        Schema::create('irs_filings', function (Blueprint $table) {
            $table->id();
            $table->string('form_type'); 
            $table->string('quarter')->nullable(); 
            $table->date('created_date')->nullable();
            $table->string('status')->default('Draft'); // Draft, Validated, Transmitted
            $table->string('submission_id')->nullable();
            $table->string('record_id')->nullable();
            $table->string('form_data')->nullable();
            $table->json('api_response')->nullable();
            $table->timestamps();
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('irs_filings');
    }
};
