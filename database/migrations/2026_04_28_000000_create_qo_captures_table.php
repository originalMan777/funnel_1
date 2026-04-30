<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qo_captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qo_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qo_submission_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('payload_json')->nullable();
            $table->boolean('is_preview')->default(false);
            $table->timestamps();

            $table->index(['qo_item_id', 'stage']);
            $table->index(['qo_item_id', 'is_preview']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qo_captures');
    }
};
