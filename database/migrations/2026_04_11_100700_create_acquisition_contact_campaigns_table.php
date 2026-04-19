<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_contact_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_contact_id')->constrained('acquisition_contacts')->cascadeOnDelete();
            $table->foreignId('acquisition_campaign_id')->constrained('acquisition_campaigns')->cascadeOnDelete();
            $table->foreignId('acquisition_sequence_id')->nullable()->constrained('acquisition_sequences')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->timestamp('entered_at')->nullable()->index();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('exited_at')->nullable();
            $table->string('exit_reason')->nullable();
            $table->timestamps();

            $table->unique(['acquisition_contact_id', 'acquisition_campaign_id'], 'acq_contact_campaigns_contact_campaign_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_contact_campaigns');
    }
};
