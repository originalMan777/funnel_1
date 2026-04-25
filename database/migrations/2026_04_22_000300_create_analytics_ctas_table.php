<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_ctas', function (Blueprint $table) {
            $table->id();
            $table->string('cta_key')->unique();
            $table->string('label');
            $table->unsignedBigInteger('cta_type_id');
            $table->string('intent_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('cta_type_id');
            $table->index('intent_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_ctas');
    }
};
