<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('acquisition_id')
                ->nullable()
                ->after('acquisition_contact_id')
                ->constrained('acquisitions')
                ->nullOnDelete();

            $table->foreignId('service_id')
                ->nullable()
                ->after('acquisition_id')
                ->constrained('services')
                ->nullOnDelete();

            $table->foreignId('acquisition_path_id')
                ->nullable()
                ->after('service_id')
                ->constrained('acquisition_paths')
                ->nullOnDelete();

            $table->string('acquisition_path_key')->nullable()->after('acquisition_path_id');
            $table->string('source_page_key')->nullable()->after('page_key');
            $table->string('source_slot_key')->nullable()->after('lead_slot_key');
            $table->string('source_popup_key')->nullable()->after('source_slot_key');
            $table->string('entry_url', 2048)->nullable()->after('source_url');
            $table->string('lead_status')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acquisition_id');
            $table->dropConstrainedForeignId('service_id');
            $table->dropConstrainedForeignId('acquisition_path_id');
            $table->dropColumn([
                'acquisition_path_key',
                'source_page_key',
                'source_slot_key',
                'source_popup_key',
                'entry_url',
                'lead_status',
            ]);
        });
    }
};
