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
        Schema::create('qo_submissions', function (Blueprint $table) {
    $table->id();

    $table->foreignId('qo_item_id')->constrained()->cascadeOnDelete();

    $table->unsignedBigInteger('lead_id')->nullable();

    $table->uuid('session_uuid');

    $table->enum('status', ['started', 'completed', 'abandoned'])->default('started');

    $table->unsignedBigInteger('current_question_id')->nullable();

    $table->integer('final_score')->nullable();
    $table->string('final_category_key')->nullable();
    $table->string('final_outcome_key')->nullable();

    $table->json('outcome_snapshot_json')->nullable();

    $table->string('source_url')->nullable();
    $table->string('referrer_url')->nullable();
    $table->json('utm_json')->nullable();

    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qo_submissions');
    }
};
