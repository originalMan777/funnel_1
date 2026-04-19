<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_campaign_id')->nullable()->constrained('acquisition_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('sequence_type')->default('outbound')->index();
            $table->string('status')->default('draft')->index();
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_sequences');
    }
};
