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
        Schema::create('staff_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('review_by'); //user id
            $table->date('review_date');
            $table->string('meeting_details')->nullable();
            $table->string('review_notes')->nullable();
            $table->string('official_review_notes')->nullable();
            $table->string('review_document')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('review_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_objectives');
    }
};
