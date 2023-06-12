<?php

use App\Models\User;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designation_id')
                ->nullable()
                ->constrained('designations', 'id')
                ->cascadeOnDelete();
            $table
                ->foreignId('assign_to')
                ->nullable()
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table->foreignId('department_id')
                    ->nullable()
                    ->constrained('departments', 'id')
                    ->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->integer('status')->default(User::USER_ACTIVE);
            $table->integer('user_role')->default(User::SUPER_ADMIN);
            $table->integer('company_id');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};