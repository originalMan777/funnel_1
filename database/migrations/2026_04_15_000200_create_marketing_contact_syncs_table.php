<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_contact_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_contact_id')->constrained('acquisition_contacts')->cascadeOnDelete();
            $table->string('provider')->index();
            $table->string('audience_key')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('external_contact_id')->nullable()->index();
            $table->string('last_sync_status')->index();
            $table->text('last_error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['acquisition_contact_id', 'provider', 'audience_key'], 'marketing_sync_contact_provider_audience_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_contact_syncs');
    }
};
