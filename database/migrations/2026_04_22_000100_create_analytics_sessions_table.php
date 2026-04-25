<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_key')->unique();
            $table->foreignId('visitor_id')->nullable()->constrained('analytics_visitors')->nullOnDelete();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('entry_page_id')->nullable();
            $table->string('entry_url', 2048)->nullable();
            $table->string('entry_path', 2048)->nullable();
            $table->string('referrer_url', 2048)->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->unsignedBigInteger('device_type_id')->nullable();
            $table->timestamps();

            $table->index('entry_page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_sessions');
    }
};
