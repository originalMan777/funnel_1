<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]))->isNotEmpty();
    }

    public function up(): void
    {
        if (! Schema::hasColumn('analytics_session_scenarios', 'assignment_type')) {
            Schema::table('analytics_session_scenarios', function (Blueprint $table) {
                $table->string('assignment_type', 16)->default('primary')->after('scenario_definition_id');
            });
        }

        DB::table('analytics_session_scenarios')
            ->whereNull('assignment_type')
            ->orWhere('assignment_type', '')
            ->update(['assignment_type' => 'primary']);

        if (! $this->indexExists('analytics_session_scenarios', 'analytics_session_scenarios_session_definition_type_unique')) {
            Schema::table('analytics_session_scenarios', function (Blueprint $table) {
                $table->unique(
                    ['session_id', 'scenario_definition_id', 'assignment_type'],
                    'analytics_session_scenarios_session_definition_type_unique'
                );
            });
        }

        if (! $this->indexExists('analytics_session_scenarios', 'analytics_session_scenarios_session_assignment_type_index')) {
            Schema::table('analytics_session_scenarios', function (Blueprint $table) {
                $table->index(
                    ['session_id', 'assignment_type'],
                    'analytics_session_scenarios_session_assignment_type_index'
                );
            });
        }

        if ($this->indexExists('analytics_session_scenarios', 'analytics_session_scenarios_session_id_unique')) {
            Schema::table('analytics_session_scenarios', function (Blueprint $table) {
                $table->dropUnique('analytics_session_scenarios_session_id_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('analytics_session_scenarios', 'analytics_session_scenarios_session_definition_type_unique')) {
            Schema::table('analytics_session_scenarios', function (Blueprint $table) {
                $table->dropUnique('analytics_session_scenarios_session_definition_type_unique');
            });
        }

        if ($this->indexExists('analytics_session_scenarios', 'analytics_session_scenarios_session_assignment_type_index')) {
            Schema::table('analytics_session_scenarios', function (Blueprint $table) {
                $table->dropIndex('analytics_session_scenarios_session_assignment_type_index');
            });
        }

        if (Schema::hasColumn('analytics_session_scenarios', 'assignment_type')) {
            Schema::table('analytics_session_scenarios', function (Blueprint $table) {
                $table->dropColumn('assignment_type');
            });
        }

        if (! $this->indexExists('analytics_session_scenarios', 'analytics_session_scenarios_session_id_unique')) {
            Schema::table('analytics_session_scenarios', function (Blueprint $table) {
                $table->unique('session_id');
            });
        }
    }
};
