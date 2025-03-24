<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\ProjectManagement\Entities\Project;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_title');
            $table->text('project_description');
            $table
                ->foreignId('parent_id')
                ->nullable()
                ->constrained('projects', 'id')
                ->cascadeOnDelete();
            $table->integer('is_completed')->default(Project::PROCESSING);
            $table
                ->foreignId('created_by')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('company_id')
                ->constrained('companies', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('department_id')
                ->nullable()
                ->constrained('departments', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('project_manager')
                ->nullable()
                ->constrained('users', 'id')
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
        Schema::dropIfExists('projects');
    }
};
