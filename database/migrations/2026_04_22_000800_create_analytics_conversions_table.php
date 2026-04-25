<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained('analytics_visitors')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('analytics_sessions')->nullOnDelete();
            $table->unsignedBigInteger('conversion_type_id');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('popup_lead_id')->nullable()->constrained('popup_leads')->nullOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('analytics_pages')->nullOnDelete();
            $table->foreignId('cta_id')->nullable()->constrained('analytics_ctas')->nullOnDelete();
            $table->foreignId('lead_box_id')->nullable()->constrained('lead_boxes')->nullOnDelete();
            $table->foreignId('lead_slot_id')->nullable()->constrained('lead_slots')->nullOnDelete();
            $table->foreignId('popup_id')->nullable()->constrained('popups')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('visitor_id');
            $table->index('lead_id');
            $table->index('popup_lead_id');
            $table->index(['conversion_type_id', 'occurred_at'], 'analytics_conversions_type_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_conversions');
    }
};
