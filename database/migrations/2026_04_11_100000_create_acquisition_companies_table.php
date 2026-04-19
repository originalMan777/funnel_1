<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('website_url', 2048)->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('industry')->nullable()->index();
            $table->string('sub_industry')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('state', 100)->nullable()->index();
            $table->string('country_code', 2)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('status')->default('active')->index();
            $table->unsignedSmallInteger('fit_score')->nullable();
            $table->string('data_source')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_companies');
    }
};
