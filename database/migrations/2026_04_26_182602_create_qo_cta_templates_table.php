<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qo_cta_templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('qo_item_id')->constrained()->cascadeOnDelete();

            // Where this CTA appears
            $table->string('slot'); 
            // pre_start, pre_answer, mid_quiz, pre_result, post_result, footer

            // Core content
            $table->string('headline')->nullable();
            $table->text('body')->nullable();

            // Primary button
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();

            // Secondary button (optional)
            $table->string('secondary_button_label')->nullable();
            $table->string('secondary_button_url')->nullable();

            // Control
            $table->boolean('is_enabled')->default(true);
            $table->integer('priority')->default(0);

            // Logic (future-proof)
            $table->json('display_rules')->nullable();
            // example: score range, category, question index, etc.

            $table->timestamps();

            $table->index(['qo_item_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qo_cta_templates');
    }
};
