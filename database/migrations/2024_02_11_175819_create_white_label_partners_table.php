<?php

use App\Models\WhiteLabelPartner;
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
        Schema::create('white_label_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('mobile');
            $table->string('fax');
            $table->integer('country_id');
            $table->string('address');
            $table->string('contact_person_name');
            $table->string('contact_person_email');
            $table->string('contact_person_number');
            $table->string('password')->default(Hash::make('password'));
            $table->integer('status')->default(WhiteLabelPartner::USER_PENDING);
            $table->string('user_name')->nullable();
            $table->string('service_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('white_label_partners');
    }
};
