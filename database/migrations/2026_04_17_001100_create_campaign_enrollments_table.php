<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_enrollments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('campaign_id');

            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('popup_lead_id')->nullable();
            $table->unsignedBigInteger('acquisition_contact_id')->nullable();

            $table->unsignedInteger('current_step_order')->default(1);
            $table->string('status')->default('active')->index();

            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            $table->string('exit_reason')->nullable();

            $table->timestamps();

            $table->foreign('campaign_id', 'campaign_enrollments_campaign_fk')
                ->references('id')
                ->on('campaigns')
                ->cascadeOnDelete();

            $table->foreign('lead_id', 'campaign_enrollments_lead_fk')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();

            $table->foreign('popup_lead_id', 'campaign_enrollments_popup_lead_fk')
                ->references('id')
                ->on('popup_leads')
                ->nullOnDelete();

            $table->foreign('acquisition_contact_id', 'campaign_enrollments_contact_fk')
                ->references('id')
                ->on('acquisition_contacts')
                ->nullOnDelete();

            $table->index(['campaign_id', 'status'], 'campaign_enrollments_campaign_status_index');
            $table->index(['status', 'next_run_at'], 'campaign_enrollments_status_next_run_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_enrollments');
    }
};
