<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('taxbandits_pdf_webhooks', function (Blueprint $table) {
            $table->id();
            $table->enum('form_type', ['940', '941'])->nullable()->index();
            $table->uuid('submission_id')->nullable();
            $table->uuid('record_id')->nullable();
            $table->string('pdf_url')->nullable();
            $table->json('payload')->nullable(); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('taxbandits_pdf_webhooks');
    }
};
