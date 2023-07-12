<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\LeaveManagement\Entities\UserLeave;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_leaves', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('leave_type')
                ->constrained('salary_items_names', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table->integer('status')->default(UserLeave::PENDING);
            $table->string('leave_message')->nullable();
            $table->string('action_message')->nullable(); // message when approving or denying by hr or upper users
            $table
                ->foreignId('action_by')
                ->constrained('users', 'id')
                ->cascadeOnDelete(); // who did approve or deny the request
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
        Schema::dropIfExists('user_leaves');
    }
};
