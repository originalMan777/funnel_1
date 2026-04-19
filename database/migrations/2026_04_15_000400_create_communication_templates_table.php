<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('channel')->default('email')->index();
            $table->string('category')->default('transactional')->index();
            $table->string('status')->default('draft')->index();
            $table->text('description')->nullable();
            $table->string('from_name_override')->nullable();
            $table->string('from_email_override')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unsignedBigInteger('current_version_id')->nullable();

            $table->index(['channel', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
    }
};
