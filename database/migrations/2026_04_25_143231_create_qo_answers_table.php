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
        Schema::create('qo_answers', function (Blueprint $table) {
    $table->id();

    $table->foreignId('qo_submission_id')->constrained()->cascadeOnDelete();
    $table->foreignId('qo_question_id')->constrained()->cascadeOnDelete();

    $table->unsignedBigInteger('qo_option_id')->nullable();

    $table->text('answer_text')->nullable();
    $table->integer('answer_number')->nullable();
    $table->json('answer_json')->nullable();

    $table->integer('score_value')->nullable();
    $table->string('category_key')->nullable();
    $table->string('outcome_key')->nullable();
    $table->boolean('is_correct')->nullable();

    $table->integer('attempt_number')->default(1);
    $table->boolean('is_final_attempt')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qo_answers');
    }
};
