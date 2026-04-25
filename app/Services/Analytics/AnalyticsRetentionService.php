<?php

namespace App\Services\Analytics;

use App\Models\Analytics\AttributionTouch;
use App\Models\Analytics\ConversionAttribution;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use App\Models\Analytics\Session;
use App\Models\Analytics\SessionScenario;
use Carbon\CarbonInterface;

class AnalyticsRetentionService
{
    /**
     * @return array<string, mixed>
     */
    public function plan(?CarbonInterface $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $sessionCutoff = $asOf->copy()->subDays((int) config('analytics.retention.raw_sessions_days', 180));
        $eventCutoff = $asOf->copy()->subDays((int) config('analytics.retention.raw_events_days', 180));
        $touchCutoff = $asOf->copy()->subDays((int) config('analytics.retention.raw_touches_days', 365));

        return [
            'as_of' => $asOf->toDateString(),
            'cutoffs' => [
                'raw_sessions_before' => $sessionCutoff->toDateString(),
                'raw_events_before' => $eventCutoff->toDateString(),
                'raw_touches_before' => $touchCutoff->toDateString(),
            ],
            'preservation_targets' => [
                'keep_rollups' => (bool) config('analytics.retention.keep_rollups', true),
                'keep_conversions' => (bool) config('analytics.retention.keep_conversions', true),
                'keep_session_scenarios' => (bool) config('analytics.retention.keep_session_scenarios', true),
                'keep_conversion_attributions' => (bool) config('analytics.retention.keep_conversion_attributions', true),
            ],
            'eligible_counts' => [
                'raw_sessions' => Session::query()->where('started_at', '<', $sessionCutoff)->count(),
                'raw_events' => Event::query()->where('occurred_at', '<', $eventCutoff)->count(),
                'raw_touches' => AttributionTouch::query()->where('occurred_at', '<', $touchCutoff)->count(),
            ],
            'dependency_snapshot' => [
                'rollups' => DailyRollup::query()->count(),
                'session_scenarios' => SessionScenario::query()->count(),
                'conversion_attributions' => ConversionAttribution::query()->count(),
            ],
            'dependency_order' => collect([
                'raw events and touches remain the source of truth',
                'daily rollups, scenario assignments, and conversion attributions must exist before any future prune',
                'safe prune work should remain opt-in and destructive actions should happen only after a confirmed plan',
            ]),
        ];
    }
}
