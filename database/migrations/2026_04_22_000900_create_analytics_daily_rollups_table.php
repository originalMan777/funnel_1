<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily_rollups', function (Blueprint $table) {
            $table->id();
            $table->date('rollup_date');
            $table->string('dimension_type');
            $table->unsignedBigInteger('dimension_id')->nullable();
            $table->string('metric_key');
            $table->decimal('metric_value', 20, 4);
            $table->timestamps();

            $table->unique(
                ['rollup_date', 'dimension_type', 'dimension_id', 'metric_key'],
                'analytics_daily_rollups_unique_metric'
            );
            $table->index('metric_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_rollups');
    }
};
