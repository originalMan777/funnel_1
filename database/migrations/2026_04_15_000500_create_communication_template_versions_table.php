<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_template_versions', function (Blueprint $table) {
            $table->id();
           $table->unsignedBigInteger('communication_template_id');

            $table->foreign('communication_template_id', 'ctv_template_fk')
                ->references('id')
                ->on('communication_templates')
                ->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->string('headline')->nullable();
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->json('variables_schema')->nullable();
            $table->json('sample_payload')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['communication_template_id', 'version_number'],
                'communication_template_versions_template_version_unique'
            );

            $table->index(
                ['communication_template_id', 'is_published'],
                'communication_template_versions_template_published_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_template_versions');
    }
};
