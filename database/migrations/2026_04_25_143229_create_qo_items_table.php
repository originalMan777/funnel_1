<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qo_items', function (Blueprint $table) {
    $table->id();

    $table->string('title');
    $table->string('internal_name')->nullable();
    $table->string('slug')->unique();

    $table->enum('type', ['quiz', 'assessment']);
    $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

    $table->string('intro_title')->nullable();
    $table->text('intro_body')->nullable();
    $table->string('start_button_label')->nullable();

    // Behavior
    $table->enum('interaction_mode', ['correctness', 'evaluative'])->default('evaluative');
    $table->enum('result_mode', ['score_range', 'category', 'mixed'])->default('score_range');

    $table->boolean('show_progress_bar')->default(true);
    $table->boolean('show_question_numbers')->default(true);
    $table->boolean('allow_back')->default(true);
    $table->boolean('auto_advance')->default(false);

    // Quiz behavior
    $table->boolean('show_correctness_feedback')->default(false);
    $table->boolean('allow_second_chance')->default(false);
    $table->integer('max_attempts_per_question')->default(1);
    $table->boolean('reveal_correct_answer_after_fail')->default(false);
    $table->boolean('show_explanations')->default(false);

    // Lead capture
    $table->enum('capture_mode', ['none', 'before_start', 'before_outcome', 'after_outcome'])->default('none');

    $table->boolean('requires_name')->default(false);
    $table->boolean('requires_email')->default(false);
    $table->boolean('requires_phone')->default(false);

    // Promo
    $table->unsignedBigInteger('intro_lead_box_id')->nullable();
    $table->enum('inline_promo_mode', ['none', 'fixed', 'rotate_interval', 'custom_ranges'])->default('none');
    $table->unsignedBigInteger('inline_lead_box_id')->nullable();
    $table->integer('inline_rotation_interval')->nullable();
    $table->unsignedBigInteger('pre_outcome_lead_box_id')->nullable();
    $table->unsignedBigInteger('post_outcome_lead_box_id')->nullable();

    // Completion
    $table->string('thank_you_title')->nullable();
    $table->text('thank_you_body')->nullable();
    $table->string('success_redirect_url')->nullable();

    $table->timestamp('published_at')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qo_items');
    }
};
