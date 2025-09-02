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
        Schema::create('eftps_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('tax_type'); 
            $table->decimal('amount', 10, 2);
            $table->string('period'); 
            $table->uuid('submission_id')->nullable();
            $table->string('status')->default('Completed'); 
            $table->enum('payment_mode', ['manual', 'auto'])->default('Manual');
            $table->string('confirmation_number')->nullable(); 
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eftps_payments');
    }
};
