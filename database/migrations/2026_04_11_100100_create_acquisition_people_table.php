<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_company_id')->nullable()->constrained('acquisition_companies')->nullOnDelete();
            $table->string('full_name')->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable()->index();
            $table->boolean('is_primary_contact')->default(false);
            $table->string('linkedin_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_people');
    }
};
