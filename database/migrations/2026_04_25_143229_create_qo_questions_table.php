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
        Schema::create('qo_questions', function (Blueprint $table) {
    $table->id();

    $table->foreignId('qo_item_id')->constrained()->cascadeOnDelete();

    $table->enum('type', ['single_choice', 'multiple_choice', 'yes_no', 'short_text', 'number']);
    $table->text('prompt');

    $table->text('helper_text')->nullable();
    $table->text('explanation_text')->nullable();

    $table->integer('sort_order')->default(0);
    $table->boolean('is_required')->default(true);

    // Overrides
    $table->boolean('allow_back_override')->nullable();
    $table->boolean('auto_advance_override')->nullable();
    $table->boolean('allow_second_chance_override')->nullable();
    $table->integer('max_attempts_override')->nullable();
    $table->boolean('show_correctness_feedback_override')->nullable();

    $table->string('media_path')->nullable();
    $table->json('settings_json')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qo_questions');
    }
};
