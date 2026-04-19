<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acquisition_contacts', function (Blueprint $table) {
            $table->string('normalized_email_key')->nullable()->after('source_label');
            $table->string('normalized_phone_key', 50)->nullable()->after('normalized_email_key');

            $table->unique('normalized_email_key', 'acq_contacts_normalized_email_unique');
            $table->unique('normalized_phone_key', 'acq_contacts_normalized_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('acquisition_contacts', function (Blueprint $table) {
            $table->dropUnique('acq_contacts_normalized_email_unique');
            $table->dropUnique('acq_contacts_normalized_phone_unique');

            $table->dropColumn([
                'normalized_email_key',
                'normalized_phone_key',
            ]);
        });
    }
};
