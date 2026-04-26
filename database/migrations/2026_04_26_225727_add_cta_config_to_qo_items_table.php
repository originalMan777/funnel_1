<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qo_items', function (Blueprint $table) {
            $table->json('cta_config')->nullable()->after('capture_mode');
        });
    }

    public function down(): void
    {
        Schema::table('qo_items', function (Blueprint $table) {
            $table->dropColumn('cta_config');
        });
    }
};
