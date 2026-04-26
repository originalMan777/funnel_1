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
       Schema::create('qo_options', function (Blueprint $table) {
    $table->id();

    $table->foreignId('qo_question_id')->constrained()->cascadeOnDelete();

    $table->string('label');
    $table->text('description')->nullable();
    $table->string('value')->nullable();

    $table->integer('sort_order')->default(0);

    // Quiz correctness
    $table->boolean('is_correct')->default(false);

    // Scoring
    $table->integer('score_value')->nullable();
    $table->string('category_key')->nullable();
    $table->string('outcome_key')->nullable();

    // Feedback
    $table->text('correct_feedback_text')->nullable();
    $table->text('incorrect_feedback_text')->nullable();

    $table->string('media_path')->nullable();
    $table->string('icon_key')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qo_options');
    }
};
