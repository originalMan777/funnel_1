<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_template_bindings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key');
            $table->string('channel')->default('email');
            $table->string('action_key');

            $table->unsignedBigInteger('communication_template_id');

            $table->foreign('communication_template_id', 'ctb_template_fk')
                ->references('id')
                ->on('communication_templates')
                ->cascadeOnDelete();

            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();

            $table->unique(
                ['event_key', 'channel', 'action_key'],
                'communication_template_bindings_event_channel_action_unique'
            );

            $table->index(
                ['event_key', 'channel', 'is_enabled'],
                'communication_template_bindings_event_channel_enabled_index'
            );

            $table->index(
                ['communication_template_id'],
                'communication_template_bindings_template_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_template_bindings');
    }
};
