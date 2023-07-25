<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_items_paid', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('payslip_id')
                ->constrained('payslips', 'id')
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
