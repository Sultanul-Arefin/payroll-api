<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Attendance\Entities\AttendanceDetail;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_details', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('attendance_id')
                ->constrained('attendances', 'id')
                ->cascadeOnDelete();
            $table->integer('office_type')->default(AttendanceDetail::FROM_OFFICE);
            $table->time('in_time');
            $table->time('out_time');
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
        Schema::dropIfExists('attendance_details');
    }
};
