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
        Schema::create('form_940_transmissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');
            $table->uuid('record_id');
            $table->string('status')->default('pending'); // pending / transmitted / failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('transmitted_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_940_transmissions');
    }
};
