<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('campaign_type')->default('outbound')->index();
            $table->string('industry')->nullable()->index();
            $table->string('market_city')->nullable()->index();
            $table->string('market_state', 100)->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('daily_touch_limit')->nullable();
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_campaigns');
    }
};
