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
        Schema::create('qo_outcomes', function (Blueprint $table) {
    $table->id();

    $table->foreignId('qo_item_id')->constrained()->cascadeOnDelete();

    $table->string('outcome_key');
    $table->string('title');
    $table->text('summary');
    $table->text('body')->nullable();

    $table->integer('sort_order')->default(0);

    // Matching
    $table->integer('min_score')->nullable();
    $table->integer('max_score')->nullable();
    $table->string('category_key')->nullable();

    // CTA
    $table->string('cta_label')->nullable();
    $table->string('cta_url')->nullable();

    // Promo override
    $table->unsignedBigInteger('lead_box_id')->nullable();

    $table->json('meta_json')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qo_outcomes');
    }
};
