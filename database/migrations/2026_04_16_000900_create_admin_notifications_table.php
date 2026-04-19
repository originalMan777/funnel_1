<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();

            $table->string('type_key')->index();
            $table->string('title');
            $table->text('message');

            $table->string('status')->default('info')->index();
            $table->unsignedInteger('priority')->default(100)->index();

            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();

            $table->string('link_url')->nullable();
            $table->string('link_label')->nullable();

            $table->string('source_type')->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
