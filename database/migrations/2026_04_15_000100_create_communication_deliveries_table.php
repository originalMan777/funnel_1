<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('communication_event_id')->constrained('communication_events')->cascadeOnDelete();
            $table->string('action_key')->index();
            $table->string('channel')->default('email')->index();
            $table->string('provider')->nullable()->index();
            $table->string('recipient_email')->nullable()->index();
            $table->string('recipient_name')->nullable();
            $table->string('subject')->nullable();
            $table->string('status')->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_deliveries');
    }
};
