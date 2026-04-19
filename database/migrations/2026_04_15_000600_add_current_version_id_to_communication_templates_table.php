<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_templates', function (Blueprint $table) {

            $table->foreign('current_version_id', 'ct_current_version_fk')
                ->references('id')
                ->on('communication_template_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('communication_templates', function (Blueprint $table) {
            $table->dropForeign('ct_current_version_fk');
        });
    }
};
