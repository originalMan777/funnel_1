<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_contact_id')->constrained('acquisition_contacts')->cascadeOnDelete();
            $table->foreignId('acquisition_company_id')->nullable()->constrained('acquisition_companies')->nullOnDelete();
            $table->foreignId('acquisition_person_id')->nullable()->constrained('acquisition_people')->nullOnDelete();
            $table->foreignId('acquisition_campaign_id')->nullable()->constrained('acquisition_campaigns')->nullOnDelete();
            $table->foreignId('acquisition_touch_id')->nullable()->constrained('acquisition_touches')->nullOnDelete();
            $table->string('event_type')->index();
            $table->string('channel')->nullable()->index();
            $table->string('actor_type')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('related_table')->nullable()->index();
            $table->unsignedBigInteger('related_id')->nullable()->index();
            $table->string('summary')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_events');
    }
};
