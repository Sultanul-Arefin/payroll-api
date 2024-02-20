<?php

use App\Models\Support;
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
        Schema::create('supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();
            $table->foreignId('category_id')
                ->constrained('help_article_categories', 'id')
                ->cascadeOnDelete();
            $table->string('subject');
            $table->string('page_name');
            $table->integer('status')->default(Support::PENDING);
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supports');
    }
};
