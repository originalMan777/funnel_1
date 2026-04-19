<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('popup_leads', function (Blueprint $table) {
            $table->foreignId('acquisition_contact_id')
                ->nullable()
                ->after('id')
                ->constrained('acquisition_contacts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('popup_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acquisition_contact_id');
        });
    }
};
