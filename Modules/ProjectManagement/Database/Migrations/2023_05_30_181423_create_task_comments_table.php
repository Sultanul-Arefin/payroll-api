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
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('task_id')
                ->constrained('tasks', 'id')
                ->cascadeOnDelete();
            $table->text('comments');
            $table
                ->foreignId('comment_by')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('parent_id')
                ->nullable()
                ->constrained('task_comments', 'id')
                ->cascadeOnDelete();
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
        Schema::dropIfExists('task_comments');
    }
};
