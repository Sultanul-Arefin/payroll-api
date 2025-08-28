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
        Schema::create('irs_filings_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filing_id')->constrained('irs_filings')->onDelete('cascade');
            $table->string('action'); 
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('irs_filings_logs');
    }
};
