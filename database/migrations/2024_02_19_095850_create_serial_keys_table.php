<?php

use App\Models\SerialKey;
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
        Schema::create('serial_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('packages', 'id')
                ->cascadeOnDelete();
            $table->foreignId('country_id')
                ->nullable()
                ->constrained('countries', 'id')
                ->nullOnDelete();
            $table->string('store')
                ->default(SerialKey::AMAZON_STORE);
            $table->string('address');
            $table->integer('key_amount');
            $table->string('product_key');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_keys');
    }
};
