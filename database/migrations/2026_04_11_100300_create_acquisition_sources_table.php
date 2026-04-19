<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_contact_id')->constrained('acquisition_contacts')->cascadeOnDelete();
            $table->string('source_type')->index();
            $table->string('source_table')->nullable()->index();
            $table->unsignedBigInteger('source_record_id')->nullable()->index();
            $table->string('page_key')->nullable()->index();
            $table->string('source_url', 2048)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('referrer_url', 2048)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_sources');
    }
};
