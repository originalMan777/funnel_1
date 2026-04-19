<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquisition_company_id')->nullable()->constrained('acquisition_companies')->nullOnDelete();
            $table->foreignId('acquisition_person_id')->nullable()->constrained('acquisition_people')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contact_type')->default('inbound')->index();
            $table->string('state')->default('new')->index();
            $table->string('source_type')->nullable()->index();
            $table->string('source_label')->nullable();
            $table->string('primary_email')->nullable()->index();
            $table->string('primary_phone', 50)->nullable()->index();
            $table->string('display_name')->nullable()->index();
            $table->string('company_name_snapshot')->nullable();
            $table->string('website_url_snapshot', 2048)->nullable();
            $table->string('city_snapshot')->nullable();
            $table->string('state_snapshot', 100)->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('next_action_at')->nullable()->index();
            $table->boolean('is_suppressed')->default(false)->index();
            $table->timestamp('suppressed_at')->nullable();
            $table->string('suppression_reason')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->longText('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_contacts');
    }
};
