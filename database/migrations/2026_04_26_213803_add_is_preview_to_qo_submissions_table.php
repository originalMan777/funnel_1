<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qo_submissions', function (Blueprint $table) {
            $table->boolean('is_preview')->default(false)->after('status');
            $table->index(['qo_item_id', 'is_preview']);
        });
    }

    public function down(): void
    {
        Schema::table('qo_submissions', function (Blueprint $table) {
            $table->dropIndex(['qo_item_id', 'is_preview']);
            $table->dropColumn('is_preview');
        });
    }
};
