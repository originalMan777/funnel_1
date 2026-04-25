<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_conversion_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_id')
                ->constrained('analytics_conversions')
                ->cascadeOnDelete();
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('analytics_sessions')
                ->nullOnDelete();
            $table->foreignId('visitor_id')
                ->nullable()
                ->constrained('analytics_visitors')
                ->nullOnDelete();
            $table->foreignId('attribution_touch_id')
                ->nullable()
                ->constrained('analytics_attribution_touches')
                ->nullOnDelete();
            $table->foreignId('landing_page_id')
                ->nullable()
                ->constrained('analytics_pages')
                ->nullOnDelete();
            $table->string('attribution_scope', 32);
            $table->string('source_key')->nullable();
            $table->string('source_label')->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('attribution_method', 64);
            $table->decimal('attribution_confidence', 8, 4)->default(0);
            $table->timestamp('occurred_at');
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->unique(['conversion_id', 'attribution_scope']);
            $table->index(['attribution_scope', 'occurred_at']);
            $table->index(['source_key', 'attribution_scope']);
            $table->index('landing_page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_conversion_attributions');
    }
};
