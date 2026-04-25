<?php

namespace Database\Seeders;

use App\Models\Analytics\EventType;
use Illuminate\Database\Seeder;

class AnalyticsEventTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('analytics.events.default_types', []) as $eventType) {
            EventType::query()->updateOrCreate(
                ['event_key' => $eventType['event_key']],
                [
                    'label' => $eventType['label'],
                    'category' => $eventType['category'],
                ],
            );
        }
    }
}
