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
        Schema::create('threshold_details', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('employee_salary_item_id')
                ->constrained('employee_salary_items', 'id')
                ->cascadeOnDelete();
            $table->integer('start_percentage_after');
            $table->integer('end_percentage_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threshold_details');
    }
};
