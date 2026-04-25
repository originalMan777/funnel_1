<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_session_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('analytics_sessions')
                ->cascadeOnDelete();
            $table->foreignId('scenario_definition_id')
                ->constrained('analytics_scenario_definitions')
                ->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->json('evidence')->nullable();
            $table->timestamps();

            $table->unique('session_id');
            $table->index('scenario_definition_id');
            $table->index('assigned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_session_scenarios');
    }
};
