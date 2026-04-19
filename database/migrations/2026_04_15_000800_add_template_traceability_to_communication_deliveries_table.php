<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_deliveries', function (Blueprint $table) {
            $table->unsignedBigInteger('communication_template_id')
                ->nullable()
                ->after('communication_event_id');

            $table->unsignedBigInteger('communication_template_version_id')
                ->nullable()
                ->after('communication_template_id');

            $table->foreign('communication_template_id', 'cd_template_fk')
                ->references('id')
                ->on('communication_templates')
                ->nullOnDelete();

            $table->foreign('communication_template_version_id', 'cd_template_version_fk')
                ->references('id')
                ->on('communication_template_versions')
                ->nullOnDelete();

            $table->boolean('is_test')->default(false)->after('communication_template_version_id');

            $table->index(['communication_template_id'], 'communication_deliveries_template_index');
            $table->index(['communication_template_version_id'], 'communication_deliveries_template_version_index');
            $table->index(['is_test'], 'communication_deliveries_is_test_index');
        });
    }

    public function down(): void
    {
        Schema::table('communication_deliveries', function (Blueprint $table) {
            $table->dropIndex('communication_deliveries_template_index');
            $table->dropIndex('communication_deliveries_template_version_index');
            $table->dropIndex('communication_deliveries_is_test_index');

            $table->dropForeign('cd_template_version_fk');
            $table->dropForeign('cd_template_fk');

            $table->dropColumn([
                'communication_template_version_id',
                'communication_template_id',
                'is_test',
            ]);
        });
    }
};
