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
        Schema::create('qo_promo_rules', function (Blueprint $table) {
    $table->id();

    $table->foreignId('qo_item_id')->constrained()->cascadeOnDelete();

    $table->enum('rule_type', ['range', 'interval', 'specific_question']);

    $table->unsignedBigInteger('lead_box_id');

    $table->integer('start_question_order')->nullable();
    $table->integer('end_question_order')->nullable();
    $table->integer('question_order')->nullable();

    $table->integer('interval_every')->nullable();

    $table->integer('priority')->default(0);
    $table->boolean('is_active')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qo_promo_rules');
    }
};
