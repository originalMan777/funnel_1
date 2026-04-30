<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qo_outcomes', function (Blueprint $table) {
            $table->string('result_headline')->nullable()->after('title');
            $table->text('interpretation')->nullable()->after('body');
            $table->json('breakdown_points')->nullable()->after('interpretation');
            $table->json('next_steps')->nullable()->after('breakdown_points');
        });
    }

    public function down(): void
    {
        Schema::table('qo_outcomes', function (Blueprint $table) {
            $table->dropColumn([
                'result_headline',
                'interpretation',
                'breakdown_points',
                'next_steps',
            ]);
        });
    }
};
