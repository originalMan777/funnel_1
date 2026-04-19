<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_id')->constrained('acquisitions')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('path_key')->unique();
            $table->string('entry_type')->nullable()->index();
            $table->string('source_context')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_paths');
    }
};
