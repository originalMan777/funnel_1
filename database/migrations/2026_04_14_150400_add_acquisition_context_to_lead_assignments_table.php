<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_assignments', function (Blueprint $table) {
            $table->foreignId('acquisition_id')
                ->nullable()
                ->after('lead_box_id')
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
        });
    }

    public function down(): void
    {
        Schema::table('lead_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acquisition_id');
            $table->dropConstrainedForeignId('service_id');
            $table->dropConstrainedForeignId('acquisition_path_id');
            $table->dropColumn('acquisition_path_key');
        });
    }
};
