<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_attribution_touches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained('analytics_visitors')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('analytics_sessions')->nullOnDelete();
            $table->foreignId('landing_page_id')->nullable()->constrained('analytics_pages')->nullOnDelete();
            $table->string('landing_url', 2048)->nullable();
            $table->string('referrer_url', 2048)->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('attribution_method');
            $table->decimal('attribution_confidence', 5, 4)->default(1);
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index('session_id');
            $table->index('visitor_id');
            $table->index(['utm_source', 'utm_medium', 'utm_campaign'], 'analytics_attr_touches_utm_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_attribution_touches');
    }
};
