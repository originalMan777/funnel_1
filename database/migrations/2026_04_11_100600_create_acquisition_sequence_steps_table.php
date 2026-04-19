<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_sequence_id')->constrained('acquisition_sequences')->cascadeOnDelete();
            $table->unsignedInteger('step_order')->index();
            $table->string('step_type')->index();
            $table->string('name');
            $table->unsignedInteger('delay_amount')->default(0);
            $table->string('delay_unit')->default('days');
            $table->boolean('requires_approval')->default(true);
            $table->string('template_key')->nullable();
            $table->string('template_subject')->nullable();
            $table->longText('template_body')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['acquisition_sequence_id', 'step_order'], 'acq_sequence_steps_sequence_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_sequence_steps');
    }
};
