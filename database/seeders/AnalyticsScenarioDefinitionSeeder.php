<?php

namespace Database\Seeders;

use App\Models\Analytics\ScenarioDefinition;
use Illuminate\Database\Seeder;

class AnalyticsScenarioDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (collect(config('analytics.scenarios.definitions', []))->merge(config('analytics.scenarios.secondary_definitions', [])) as $definition) {
            ScenarioDefinition::query()->updateOrCreate(
                ['scenario_key' => $definition['scenario_key']],
                [
                    'label' => $definition['label'],
                    'description' => $definition['description'] ?? null,
                    'priority' => $definition['priority'] ?? 100,
                    'is_active' => true,
                ],
            );
        }
    }
}
