<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_steps', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('campaign_id');
            $table->unsignedInteger('step_order');

            $table->unsignedInteger('delay_amount')->default(0);
            $table->string('delay_unit')->default('days');

            $table->string('send_mode')->default('template');

            $table->unsignedBigInteger('template_id')->nullable();

            $table->string('subject')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();

            $table->boolean('is_enabled')->default(true)->index();

            $table->timestamps();

            $table->foreign('campaign_id', 'campaign_steps_campaign_fk')
                ->references('id')
                ->on('campaigns')
                ->cascadeOnDelete();

            $table->foreign('template_id', 'campaign_steps_template_fk')
                ->references('id')
                ->on('communication_templates')
                ->nullOnDelete();

            $table->unique(['campaign_id', 'step_order'], 'campaign_steps_campaign_step_unique');
            $table->index(['campaign_id', 'is_enabled'], 'campaign_steps_campaign_enabled_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_steps');
    }
};
