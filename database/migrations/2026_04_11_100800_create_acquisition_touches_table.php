<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_touches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_contact_id')->constrained('acquisition_contacts')->cascadeOnDelete();
            $table->foreignId('acquisition_campaign_id')->nullable()->constrained('acquisition_campaigns')->nullOnDelete();
            $table->foreignId('acquisition_sequence_id')->nullable()->constrained('acquisition_sequences')->nullOnDelete();
            $table->foreignId('acquisition_sequence_step_id')->nullable()->constrained('acquisition_sequence_steps')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('touch_type')->index();
            $table->string('status')->default('pending')->index();
            $table->timestamp('scheduled_for')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('recipient_email')->nullable()->index();
            $table->string('recipient_phone', 50)->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable()->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('response_detected_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_touches');
    }
};
