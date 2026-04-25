<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained('analytics_visitors')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('analytics_sessions')->nullOnDelete();
            $table->foreignId('event_type_id')->constrained('analytics_event_types')->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('analytics_pages')->nullOnDelete();
            $table->foreignId('cta_id')->nullable()->constrained('analytics_ctas')->nullOnDelete();
            $table->foreignId('lead_box_id')->nullable()->constrained('lead_boxes')->nullOnDelete();
            $table->foreignId('lead_slot_id')->nullable()->constrained('lead_slots')->nullOnDelete();
            $table->foreignId('popup_id')->nullable()->constrained('popups')->nullOnDelete();
            $table->foreignId('surface_id')->nullable()->constrained('analytics_surfaces')->nullOnDelete();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['session_id', 'occurred_at'], 'analytics_events_session_occurred_idx');
            $table->index(['visitor_id', 'occurred_at'], 'analytics_events_visitor_occurred_idx');
            $table->index(['event_type_id', 'occurred_at'], 'analytics_events_type_occurred_idx');
            $table->index(['page_id', 'occurred_at'], 'analytics_events_page_occurred_idx');
            $table->index(['cta_id', 'occurred_at'], 'analytics_events_cta_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
