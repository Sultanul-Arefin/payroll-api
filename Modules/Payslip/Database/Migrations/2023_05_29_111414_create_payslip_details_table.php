<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payslip_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('payslip_id');
            $table
                ->foreign('payslip_id')
                ->references('id')
                ->on('payslips')
                ->cascadeOnDelete();
            $table
                ->foreignId('salary_item_id')
                ->constrained('salary_items_names', 'id')
                ->cascadeOnDelete();
            $table->double('amount', 8, 2); // can store total 8 digits, 6 before decimal point & 2 after decimal point
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
        Schema::dropIfExists('salary_items_paid');
    }
};
