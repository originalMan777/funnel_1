<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->index();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('acquisition_contact_id')->nullable()->constrained('acquisition_contacts')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_events');
    }
};
