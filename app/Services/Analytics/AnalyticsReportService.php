<?php

namespace App\Services\Analytics;

use App\Models\Analytics\ConversionAttribution;
use App\Models\Analytics\Cta;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use App\Models\Analytics\Page;
use App\Models\Analytics\Session;
use App\Models\Analytics\SessionScenario;
use App\Models\LeadBox;
use App\Models\Popup;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnalyticsReportService
{
    /**
     * @var array<string, Collection<int, Session>>
     */
    private array $timingSessionCache = [];

    /**
     * @param  list<string>  $metricKeys
     * @return Collection<int, array{rollup_date:string,metric_key:string,metric_value:float}>
     */
    public function dailyTotals(CarbonInterface $from, CarbonInterface $to, array $metricKeys): Collection
    {
        [$rangeStart, $rangeEnd] = $this->rangeBounds($from, $to);

        return DailyRollup::query()
            ->whereBetween('rollup_date', [$rangeStart, $rangeEnd])
            ->where('dimension_type', RollupService::DIMENSION_TOTAL)
            ->whereIn('metric_key', $metricKeys)
            ->orderBy('rollup_date')
            ->get()
            ->map(fn (DailyRollup $rollup) => [
                'rollup_date' => $rollup->rollup_date->toDateString(),
                'metric_key' => $rollup->metric_key,
                'metric_value' => (float) $rollup->metric_value,
            ]);
    }

    /**
     * @return array<string, float|null>
     */
    public function overviewSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        $sessions = $this->sessionsForTiming($from, $to);
        $sessionDurations = $sessions
            ->map(fn (Session $session) => $this->eventBasedSessionDurationSeconds($session))
            ->filter(fn (?float $seconds) => $seconds !== null)
            ->values();
        $timeToConversion = $sessions
            ->map(function (Session $session): ?float {
                $conversion = $session->conversions
                    ->sortBy(fn ($item) => sprintf('%s-%010d', optional($item->occurred_at)?->format('YmdHis'), $item->id))
                    ->first();

                return $this->elapsedSeconds($session->started_at, $conversion?->occurred_at);
            })
            ->filter(fn (?float $seconds) => $seconds !== null)
            ->values();

        return [
            'page_views' => $this->metricTotal($from, $to, RollupService::DIMENSION_PAGE, RollupService::METRIC_PAGE_VIEWS),
            'cta_clicks' => $this->metricTotal($from, $to, RollupService::DIMENSION_CTA, RollupService::METRIC_CTA_CLICKS),
            'lead_form_submissions' => $this->metricTotal($from, $to, RollupService::DIMENSION_LEAD_BOX, RollupService::METRIC_LEAD_FORM_SUBMISSIONS),
            'popup_submissions' => $this->metricTotal($from, $to, RollupService::DIMENSION_POPUP, RollupService::METRIC_POPUP_SUBMISSIONS),
            'conversions' => $this->metricTotal($from, $to, RollupService::DIMENSION_TOTAL, RollupService::METRIC_CONVERSION_TOTAL),
            'average_session_duration_seconds' => $this->averageSeconds($sessionDurations),
            'median_session_duration_seconds' => $this->medianSeconds($sessionDurations),
            'average_time_to_conversion_seconds' => $this->averageSeconds($timeToConversion),
            'median_time_to_conversion_seconds' => $this->medianSeconds($timeToConversion),
        ];
    }

    /**
     * @return Collection<int, array<string, float|string>>
     */
    public function overviewTrend(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->metricTrend($from, $to, [
            'page_views' => [
                'dimension_type' => RollupService::DIMENSION_PAGE,
                'metric_key' => RollupService::METRIC_PAGE_VIEWS,
            ],
            'cta_clicks' => [
                'dimension_type' => RollupService::DIMENSION_CTA,
                'metric_key' => RollupService::METRIC_CTA_CLICKS,
            ],
            'lead_form_submissions' => [
                'dimension_type' => RollupService::DIMENSION_LEAD_BOX,
                'metric_key' => RollupService::METRIC_LEAD_FORM_SUBMISSIONS,
            ],
            'popup_submissions' => [
                'dimension_type' => RollupService::DIMENSION_POPUP,
                'metric_key' => RollupService::METRIC_POPUP_SUBMISSIONS,
            ],
            'conversions' => [
                'dimension_type' => RollupService::DIMENSION_TOTAL,
                'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function pagePerformance(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $timings = $this->pageTimingMetrics($from, $to);

        return $this->dimensionSummary(
            modelClass: Page::class,
            dimensionType: RollupService::DIMENSION_PAGE,
            metricKeys: [
                RollupService::METRIC_PAGE_VIEWS,
                RollupService::METRIC_PAGE_CONVERSIONS,
            ],
            keyField: 'page_key',
            labelField: 'label',
            extraFields: ['category'],
            aliases: [
                RollupService::METRIC_PAGE_VIEWS => 'views',
                RollupService::METRIC_PAGE_CONVERSIONS => 'conversions',
            ],
            from: $from,
            to: $to,
        )->map(function (array $row) use ($timings): array {
            $rowTimings = $timings[$row['id']] ?? [];

            return [
                ...$row,
                'conversion_rate' => $this->safeRate($row['conversions'], $row['views']),
                'avg_time_to_cta_click_seconds' => $rowTimings['avg_time_to_cta_click_seconds'] ?? null,
                'avg_time_to_conversion_seconds' => $rowTimings['avg_time_to_conversion_seconds'] ?? null,
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function ctaPerformance(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $timings = $this->ctaTimingMetrics($from, $to);
        $attributions = $this->conversionTouchAttributionMap($from, $to);

        return $this->dimensionSummary(
            modelClass: Cta::class,
            dimensionType: RollupService::DIMENSION_CTA,
            metricKeys: [
                RollupService::METRIC_CTA_IMPRESSIONS,
                RollupService::METRIC_CTA_CLICKS,
                RollupService::METRIC_CTA_CONVERSIONS,
            ],
            keyField: 'cta_key',
            labelField: 'label',
            extraFields: ['intent_key'],
            aliases: [
                RollupService::METRIC_CTA_IMPRESSIONS => 'impressions',
                RollupService::METRIC_CTA_CLICKS => 'clicks',
                RollupService::METRIC_CTA_CONVERSIONS => 'conversions',
            ],
            from: $from,
            to: $to,
        )->map(function (array $row) use ($timings, $attributions): array {
            $rowTimings = $timings[$row['id']] ?? [];
            $attribution = $attributions[$row['id']] ?? [];

            return [
                ...$row,
                'ctr' => $this->safeRate($row['clicks'], $row['impressions']),
                'conversion_rate' => $this->safeRate($row['conversions'], $row['clicks']),
                'avg_time_to_click_seconds' => $rowTimings['avg_time_to_click_seconds'] ?? null,
                'avg_click_to_conversion_seconds' => $rowTimings['avg_click_to_conversion_seconds'] ?? null,
                'median_click_to_conversion_seconds' => $rowTimings['median_click_to_conversion_seconds'] ?? null,
                'conversion_touch_conversions' => $attribution['attributed_conversion_count'] ?? 0,
            ];
        });
    }

    /**
     * @return Collection<int, array<string, float|string>>
     */
    public function ctaTrend(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->metricTrend($from, $to, [
            'impressions' => [
                'dimension_type' => RollupService::DIMENSION_CTA,
                'metric_key' => RollupService::METRIC_CTA_IMPRESSIONS,
            ],
            'clicks' => [
                'dimension_type' => RollupService::DIMENSION_CTA,
                'metric_key' => RollupService::METRIC_CTA_CLICKS,
            ],
            'conversions' => [
                'dimension_type' => RollupService::DIMENSION_CTA,
                'metric_key' => RollupService::METRIC_CTA_CONVERSIONS,
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function leadBoxPerformance(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $timings = $this->leadBoxTimingMetrics($from, $to);

        return $this->dimensionSummary(
            modelClass: LeadBox::class,
            dimensionType: RollupService::DIMENSION_LEAD_BOX,
            metricKeys: [
                RollupService::METRIC_LEAD_BOX_IMPRESSIONS,
                RollupService::METRIC_LEAD_BOX_CLICKS,
                RollupService::METRIC_LEAD_FORM_SUBMISSIONS,
                RollupService::METRIC_LEAD_FORM_FAILURES,
            ],
            keyField: 'internal_name',
            labelField: 'title',
            extraFields: ['type'],
            aliases: [
                RollupService::METRIC_LEAD_BOX_IMPRESSIONS => 'impressions',
                RollupService::METRIC_LEAD_BOX_CLICKS => 'clicks',
                RollupService::METRIC_LEAD_FORM_SUBMISSIONS => 'submissions',
                RollupService::METRIC_LEAD_FORM_FAILURES => 'failures',
            ],
            from: $from,
            to: $to,
        )->map(function (array $row) use ($timings): array {
            $rowTimings = $timings[$row['id']] ?? [];

            return [
                ...$row,
                'click_through_rate' => $this->safeRate($row['clicks'], $row['impressions']),
                'submission_rate' => $this->safeRate($row['submissions'], $row['clicks']),
                'avg_impression_to_submit_seconds' => $rowTimings['avg_impression_to_submit_seconds'] ?? null,
                'avg_click_to_submit_seconds' => $rowTimings['avg_click_to_submit_seconds'] ?? null,
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function popupPerformance(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $timings = $this->popupTimingMetrics($from, $to);
        $conversionTouchMap = $this->conversionTouchAttributionMap($from, $to);

        return $this->dimensionSummary(
            modelClass: Popup::class,
            dimensionType: RollupService::DIMENSION_POPUP,
            metricKeys: [
                RollupService::METRIC_POPUP_ELIGIBLE,
                RollupService::METRIC_POPUP_IMPRESSIONS,
                RollupService::METRIC_POPUP_OPENS,
                RollupService::METRIC_POPUP_DISMISSALS,
                RollupService::METRIC_POPUP_SUBMISSIONS,
            ],
            keyField: 'slug',
            labelField: 'name',
            extraFields: ['type'],
            aliases: [
                RollupService::METRIC_POPUP_ELIGIBLE => 'eligible',
                RollupService::METRIC_POPUP_IMPRESSIONS => 'impressions',
                RollupService::METRIC_POPUP_OPENS => 'opens',
                RollupService::METRIC_POPUP_DISMISSALS => 'dismissals',
                RollupService::METRIC_POPUP_SUBMISSIONS => 'submissions',
            ],
            from: $from,
            to: $to,
        )->map(function (array $row) use ($timings, $conversionTouchMap): array {
            $rowTimings = $timings[$row['id']] ?? [];
            $touchCounts = $conversionTouchMap['popup'][$row['id']] ?? [];

            return [
                ...$row,
                'open_rate' => $this->safeRate($row['opens'], $row['impressions']),
                'submission_rate' => $this->safeRate($row['submissions'], $row['opens']),
                'avg_open_to_submit_seconds' => $rowTimings['avg_open_to_submit_seconds'] ?? null,
                'avg_open_to_dismiss_seconds' => $rowTimings['avg_open_to_dismiss_seconds'] ?? null,
                'median_open_to_submit_seconds' => $rowTimings['median_open_to_submit_seconds'] ?? null,
                'conversion_touch_conversions' => $touchCounts['count'] ?? 0,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function conversionSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        $totals = $this->dailyTotals($from, $to, [RollupService::METRIC_CONVERSION_TOTAL]);
        [$rangeStart, $rangeEnd] = $this->rangeBounds($from, $to);
        $sessions = $this->sessionsForTiming($from, $to);
        $timeToConversion = $sessions
            ->map(function (Session $session): ?float {
                $conversion = $session->conversions
                    ->sortBy(fn ($item) => sprintf('%s-%010d', optional($item->occurred_at)?->format('YmdHis'), $item->id))
                    ->first();

                return $this->elapsedSeconds($session->started_at, $conversion?->occurred_at);
            })
            ->filter(fn (?float $seconds) => $seconds !== null)
            ->values();

        $byType = DailyRollup::query()
            ->whereBetween('rollup_date', [$rangeStart, $rangeEnd])
            ->where('dimension_type', RollupService::DIMENSION_CONVERSION_TYPE)
            ->where('metric_key', RollupService::METRIC_CONVERSION_TOTAL)
            ->groupBy('dimension_id')
            ->selectRaw('dimension_id, SUM(metric_value) as total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'conversion_type_id' => (int) $row->dimension_id,
                'label' => $this->conversionTypeLabel((int) $row->dimension_id),
                'total' => (float) $row->total,
            ]);

        return [
            'total' => $totals,
            'by_type' => $byType,
            'average_time_to_conversion_seconds' => $this->averageSeconds($timeToConversion),
            'median_time_to_conversion_seconds' => $this->medianSeconds($timeToConversion),
        ];
    }

    /**
     * @return Collection<int, array<string, float|string>>
     */
    public function conversionTrend(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->metricTrend($from, $to, [
            'conversions' => [
                'dimension_type' => RollupService::DIMENSION_TOTAL,
                'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function scenarioPerformance(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return SessionScenario::query()
            ->where('assignment_type', SessionScenario::TYPE_PRIMARY)
            ->whereHas('session', function ($sessionQuery) use ($from, $to): void {
                $sessionQuery->whereBetween('started_at', [
                    $from->copy()->startOfDay()->toDateTimeString(),
                    $to->copy()->endOfDay()->toDateTimeString(),
                ]);
            })
            ->with([
                'scenarioDefinition',
                'session.events',
                'session.conversions',
            ])
            ->get()
            ->groupBy('scenario_definition_id')
            ->map(function (Collection $group) {
                $first = $group->first();
                $definition = $first?->scenarioDefinition;
                $sessions = $group->count();
                $convertedSessions = $group
                    ->filter(fn (SessionScenario $assignment) => $assignment->session?->conversions->isNotEmpty())
                    ->count();
                $conversionTotal = $group
                    ->sum(fn (SessionScenario $assignment) => $assignment->session?->conversions->count() ?? 0);
                $eventSamples = $group
                    ->map(fn (SessionScenario $assignment) => $assignment->session?->events->count() ?? 0)
                    ->values();
                $durationSamples = $group
                    ->map(fn (SessionScenario $assignment) => $this->eventBasedSessionDurationSeconds($assignment->session))
                    ->filter(fn (?float $seconds) => $seconds !== null)
                    ->values();

                return [
                    'scenario_key' => (string) $definition?->scenario_key,
                    'label' => (string) $definition?->label,
                    'description' => $definition?->description,
                    'sessions' => $sessions,
                    'converted_sessions' => $convertedSessions,
                    'conversion_total' => $conversionTotal,
                    'conversion_rate' => $this->safeRate((float) $convertedSessions, (float) $sessions),
                    'average_events' => round((float) $eventSamples->avg(), 2),
                    'average_session_duration_seconds' => $this->averageSeconds($durationSamples),
                    'median_session_duration_seconds' => $this->medianSeconds($durationSamples),
                ];
            })
            ->filter(fn (array $row) => $row['scenario_key'] !== '')
            ->sortByDesc('sessions')
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function secondaryScenarioPerformance(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return SessionScenario::query()
            ->where('assignment_type', SessionScenario::TYPE_SECONDARY)
            ->whereHas('session', function ($sessionQuery) use ($from, $to): void {
                $sessionQuery->whereBetween('started_at', [
                    $from->copy()->startOfDay()->toDateTimeString(),
                    $to->copy()->endOfDay()->toDateTimeString(),
                ]);
            })
            ->with([
                'scenarioDefinition',
                'session.conversions',
            ])
            ->get()
            ->groupBy('scenario_definition_id')
            ->map(function (Collection $group) {
                $definition = $group->first()?->scenarioDefinition;
                $sessions = $group->count();
                $convertedSessions = $group->filter(fn (SessionScenario $assignment) => $assignment->session?->conversions->isNotEmpty())->count();

                return [
                    'scenario_key' => (string) $definition?->scenario_key,
                    'label' => (string) $definition?->label,
                    'description' => $definition?->description,
                    'sessions' => $sessions,
                    'converted_sessions' => $convertedSessions,
                    'conversion_rate' => $this->safeRate((float) $convertedSessions, (float) $sessions),
                ];
            })
            ->filter(fn (array $row) => $row['scenario_key'] !== '')
            ->sortByDesc('sessions')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function attributionSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        [$rangeStart, $rangeEnd] = $this->rangeBounds($from, $to);
        $baseQuery = ConversionAttribution::query()
            ->whereBetween('occurred_at', [$rangeStart, $rangeEnd]);

        $rowsByScope = collect([
            AnalyticsAttributionService::SCOPE_FIRST_TOUCH,
            AnalyticsAttributionService::SCOPE_LAST_TOUCH,
            AnalyticsAttributionService::SCOPE_CONVERSION_TOUCH,
        ])->mapWithKeys(function (string $scope) use ($baseQuery) {
            $rows = (clone $baseQuery)
                ->where('attribution_scope', $scope)
                ->selectRaw('source_key, source_label, attribution_method, COUNT(*) as conversion_count')
                ->groupBy('source_key', 'source_label', 'attribution_method')
                ->orderByDesc('conversion_count')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'source_key' => $row->source_key,
                    'source_label' => $row->source_label ?: 'Unlabeled source',
                    'attribution_method' => $row->attribution_method,
                    'conversion_count' => (int) $row->conversion_count,
                ]);

            return [$scope => $rows];
        });

        $attributedConversionIds = ConversionAttribution::query()
            ->whereBetween('occurred_at', [$rangeStart, $rangeEnd])
            ->distinct()
            ->pluck('conversion_id');

        $totalConversions = $this->metricTotal($from, $to, RollupService::DIMENSION_TOTAL, RollupService::METRIC_CONVERSION_TOTAL);

        return [
            'overview' => [
                'attributed_conversions' => $attributedConversionIds->count(),
                'unattributed_conversions' => max((int) $totalConversions - $attributedConversionIds->count(), 0),
            ],
            'first_touch' => $rowsByScope->get(AnalyticsAttributionService::SCOPE_FIRST_TOUCH, collect()),
            'last_touch' => $rowsByScope->get(AnalyticsAttributionService::SCOPE_LAST_TOUCH, collect()),
            'conversion_touch' => $rowsByScope->get(AnalyticsAttributionService::SCOPE_CONVERSION_TOUCH, collect()),
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<string>  $metricKeys
     * @param  array<string, string>  $aliases
     * @param  list<string>  $extraFields
     * @return Collection<int, array<string, mixed>>
     */
    private function dimensionSummary(
        string $modelClass,
        string $dimensionType,
        array $metricKeys,
        string $keyField,
        string $labelField,
        array $extraFields,
        array $aliases,
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection {
        [$rangeStart, $rangeEnd] = $this->rangeBounds($from, $to);

        $rollups = DailyRollup::query()
            ->whereBetween('rollup_date', [$rangeStart, $rangeEnd])
            ->where('dimension_type', $dimensionType)
            ->whereIn('metric_key', $metricKeys)
            ->get()
            ->groupBy('dimension_id');

        if ($rollups->isEmpty()) {
            return collect();
        }

        $dimensions = $modelClass::query()
            ->whereIn('id', $rollups->keys()->filter()->values())
            ->get()
            ->keyBy('id');

        return $rollups
            ->map(function (Collection $group, $dimensionId) use ($aliases, $dimensions, $keyField, $labelField, $extraFields) {
                $dimension = $dimensions->get((int) $dimensionId);

                if (! $dimension) {
                    return null;
                }

                $summary = [
                    'id' => (int) $dimensionId,
                    'key' => (string) data_get($dimension, $keyField),
                    'label' => (string) data_get($dimension, $labelField),
                ];

                foreach ($extraFields as $field) {
                    $summary[$field] = data_get($dimension, $field);
                }

                foreach ($aliases as $metricKey => $alias) {
                    $summary[$alias] = (float) $group
                        ->where('metric_key', $metricKey)
                        ->sum(fn (DailyRollup $rollup) => (float) $rollup->metric_value);
                }

                return $summary;
            })
            ->filter()
            ->sortByDesc(function (array $row) use ($aliases) {
                return collect(array_values($aliases))
                    ->sum(fn (string $alias) => (float) ($row[$alias] ?? 0));
            })
            ->values();
    }

    /**
     * @param  array<string, array{dimension_type:string, metric_key:string}>  $definitions
     * @return Collection<int, array<string, float|string>>
     */
    private function metricTrend(CarbonInterface $from, CarbonInterface $to, array $definitions): Collection
    {
        [$rangeStart, $rangeEnd] = $this->rangeBounds($from, $to);

        $query = DailyRollup::query()
            ->whereBetween('rollup_date', [$rangeStart, $rangeEnd])
            ->where(function ($query) use ($definitions): void {
                foreach ($definitions as $definition) {
                    $query->orWhere(function ($metricQuery) use ($definition): void {
                        $metricQuery
                            ->where('dimension_type', $definition['dimension_type'])
                            ->where('metric_key', $definition['metric_key']);
                    });
                }
            })
            ->selectRaw('DATE(rollup_date) as rollup_date, dimension_type, metric_key, SUM(metric_value) as total')
            ->groupByRaw('DATE(rollup_date), dimension_type, metric_key')
            ->get();

        $index = $query
            ->mapWithKeys(fn ($row) => [
                sprintf('%s|%s|%s', $row->rollup_date, $row->dimension_type, $row->metric_key) => (float) $row->total,
            ]);

        $rows = collect();
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $row = ['date' => $date];

            foreach ($definitions as $alias => $definition) {
                $row[$alias] = (float) ($index->get(sprintf(
                    '%s|%s|%s',
                    $date,
                    $definition['dimension_type'],
                    $definition['metric_key'],
                )) ?? 0);
            }

            $rows->push($row);
            $cursor = $cursor->copy()->addDay();
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, float|null>>
     */
    private function pageTimingMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $samples = [];

        foreach ($this->sessionsForTiming($from, $to) as $session) {
            $events = $this->orderedEvents($session);
            $pageViews = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'page.view' && $event->page_id !== null)
                ->groupBy('page_id')
                ->map(fn (Collection $group) => $group->first());
            $ctaClicks = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'cta.click' && $event->page_id !== null)
                ->groupBy('page_id')
                ->map(fn (Collection $group) => $group->first());
            $conversions = $session->conversions->filter(fn ($conversion) => $conversion->page_id !== null)
                ->sortBy(fn ($conversion) => sprintf('%s-%010d', optional($conversion->occurred_at)?->format('YmdHis'), $conversion->id))
                ->groupBy('page_id')
                ->map(fn (Collection $group) => $group->first());

            foreach ($pageViews as $pageId => $pageView) {
                $pageId = (int) $pageId;
                $toClick = $this->elapsedSeconds($pageView?->occurred_at, $ctaClicks->get($pageId)?->occurred_at);
                $toConversion = $this->elapsedSeconds($pageView?->occurred_at, $conversions->get($pageId)?->occurred_at);

                if ($toClick !== null) {
                    $samples[$pageId]['avg_time_to_cta_click_seconds'][] = $toClick;
                }

                if ($toConversion !== null) {
                    $samples[$pageId]['avg_time_to_conversion_seconds'][] = $toConversion;
                }
            }
        }

        return $this->summarizeMetricSamples($samples);
    }

    /**
     * @return array<int, array<string, float|null>>
     */
    private function ctaTimingMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $samples = [];

        foreach ($this->sessionsForTiming($from, $to) as $session) {
            $events = $this->orderedEvents($session);
            $pageViews = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'page.view' && $event->page_id !== null)
                ->groupBy('page_id')
                ->map(fn (Collection $group) => $group->first());
            $clicksByCta = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'cta.click' && $event->cta_id !== null)
                ->groupBy('cta_id')
                ->map(fn (Collection $group) => $group->first());
            $conversionsByCta = $session->conversions->filter(fn ($conversion) => $conversion->cta_id !== null)
                ->sortBy(fn ($conversion) => sprintf('%s-%010d', optional($conversion->occurred_at)?->format('YmdHis'), $conversion->id))
                ->groupBy('cta_id')
                ->map(fn (Collection $group) => $group->first());

            foreach ($clicksByCta as $ctaId => $click) {
                $ctaId = (int) $ctaId;
                $toClick = $this->elapsedSeconds($pageViews->get($click->page_id)?->occurred_at, $click?->occurred_at);
                $toConversion = $this->elapsedSeconds($click?->occurred_at, $conversionsByCta->get($ctaId)?->occurred_at);

                if ($toClick !== null) {
                    $samples[$ctaId]['avg_time_to_click_seconds'][] = $toClick;
                }

                if ($toConversion !== null) {
                    $samples[$ctaId]['avg_click_to_conversion_seconds'][] = $toConversion;
                    $samples[$ctaId]['median_click_to_conversion_seconds'][] = $toConversion;
                }
            }
        }

        return $this->summarizeMetricSamples($samples);
    }

    /**
     * @return array<int, array<string, float|null>>
     */
    private function leadBoxTimingMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $samples = [];

        foreach ($this->sessionsForTiming($from, $to) as $session) {
            $events = $this->orderedEvents($session);
            $impressions = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'lead_box.impression' && $event->lead_box_id !== null)
                ->groupBy('lead_box_id')
                ->map(fn (Collection $group) => $group->first());
            $clicks = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'lead_box.click' && $event->lead_box_id !== null)
                ->groupBy('lead_box_id')
                ->map(fn (Collection $group) => $group->first());
            $submits = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'lead_form.submitted' && $event->lead_box_id !== null)
                ->groupBy('lead_box_id')
                ->map(fn (Collection $group) => $group->first());

            foreach ($submits as $leadBoxId => $submit) {
                $leadBoxId = (int) $leadBoxId;
                $fromImpression = $this->elapsedSeconds($impressions->get($leadBoxId)?->occurred_at, $submit?->occurred_at);
                $fromClick = $this->elapsedSeconds($clicks->get($leadBoxId)?->occurred_at, $submit?->occurred_at);

                if ($fromImpression !== null) {
                    $samples[$leadBoxId]['avg_impression_to_submit_seconds'][] = $fromImpression;
                }

                if ($fromClick !== null) {
                    $samples[$leadBoxId]['avg_click_to_submit_seconds'][] = $fromClick;
                }
            }
        }

        return $this->summarizeMetricSamples($samples);
    }

    /**
     * @return array<int, array<string, float|null>>
     */
    private function popupTimingMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $samples = [];

        foreach ($this->sessionsForTiming($from, $to) as $session) {
            $events = $this->orderedEvents($session);
            $opens = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'popup.opened' && $event->popup_id !== null)
                ->groupBy('popup_id')
                ->map(fn (Collection $group) => $group->first());
            $submits = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'popup.submitted' && $event->popup_id !== null)
                ->groupBy('popup_id')
                ->map(fn (Collection $group) => $group->first());
            $dismissals = $events->filter(fn (Event $event) => $event->eventType?->event_key === 'popup.dismissed' && $event->popup_id !== null)
                ->groupBy('popup_id')
                ->map(fn (Collection $group) => $group->first());

            foreach ($opens as $popupId => $open) {
                $popupId = (int) $popupId;
                $toSubmit = $this->elapsedSeconds($open?->occurred_at, $submits->get($popupId)?->occurred_at);
                $toDismiss = $this->elapsedSeconds($open?->occurred_at, $dismissals->get($popupId)?->occurred_at);

                if ($toSubmit !== null) {
                    $samples[$popupId]['avg_open_to_submit_seconds'][] = $toSubmit;
                    $samples[$popupId]['median_open_to_submit_seconds'][] = $toSubmit;
                }

                if ($toDismiss !== null) {
                    $samples[$popupId]['avg_open_to_dismiss_seconds'][] = $toDismiss;
                }
            }
        }

        return $this->summarizeMetricSamples($samples);
    }

    /**
     * @param  array<int, array<string, array<int, float>>>  $samples
     * @return array<int, array<string, float|null>>
     */
    private function summarizeMetricSamples(array $samples): array
    {
        return collect($samples)
            ->map(function (array $metrics): array {
                return collect($metrics)->map(function (array $values, string $metricKey): ?float {
                    $collection = collect($values);

                    return str_contains($metricKey, 'median_')
                        ? $this->medianSeconds($collection)
                        : $this->averageSeconds($collection);
                })->all();
            })
            ->all();
    }

    /**
     * @return array<int, array<string, int>>
     */
    private function conversionTouchAttributionMap(CarbonInterface $from, CarbonInterface $to): array
    {
        [$rangeStart, $rangeEnd] = $this->rangeBounds($from, $to);
        $rows = ConversionAttribution::query()
            ->whereBetween('occurred_at', [$rangeStart, $rangeEnd])
            ->where('attribution_scope', AnalyticsAttributionService::SCOPE_CONVERSION_TOUCH)
            ->get();
        $ctaMap = $rows->filter(fn (ConversionAttribution $row) => str_starts_with((string) $row->source_key, 'cta:'))
            ->groupBy(fn (ConversionAttribution $row) => (int) str_replace('cta:', '', (string) $row->source_key))
            ->map(fn (Collection $group) => ['attributed_conversion_count' => $group->count()])
            ->all();
        $popupMap = $rows->filter(fn (ConversionAttribution $row) => str_starts_with((string) $row->source_key, 'popup:'))
            ->groupBy(fn (ConversionAttribution $row) => (int) str_replace('popup:', '', (string) $row->source_key))
            ->map(fn (Collection $group) => ['count' => $group->count()])
            ->all();

        return [
            ...$ctaMap,
            'popup' => $popupMap,
        ];
    }

    /**
     * @return Collection<int, Session>
     */
    private function sessionsForTiming(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $cacheKey = sprintf('%s|%s', $from->copy()->startOfDay()->toDateTimeString(), $to->copy()->endOfDay()->toDateTimeString());

        if (! array_key_exists($cacheKey, $this->timingSessionCache)) {
            $this->timingSessionCache[$cacheKey] = Session::query()
                ->whereBetween('started_at', [
                    $from->copy()->startOfDay()->toDateTimeString(),
                    $to->copy()->endOfDay()->toDateTimeString(),
                ])
                ->with([
                    'events.eventType',
                    'conversions',
                ])
                ->get();
        }

        return $this->timingSessionCache[$cacheKey];
    }

    /**
     * @return Collection<int, Event>
     */
    private function orderedEvents(?Session $session): Collection
    {
        if (! $session) {
            return collect();
        }

        return $session->events
            ->sortBy(fn (Event $event) => sprintf('%s-%010d', optional($event->occurred_at)?->format('YmdHis'), $event->id))
            ->values();
    }

    private function eventBasedSessionDurationSeconds(?Session $session): ?float
    {
        $events = $this->orderedEvents($session);

        if ($events->isEmpty()) {
            return null;
        }

        return $this->elapsedSeconds($events->first()?->occurred_at, $events->last()?->occurred_at);
    }

    private function elapsedSeconds($start, $end): ?float
    {
        if (! $start || ! $end) {
            return null;
        }

        $seconds = $end->getTimestamp() - $start->getTimestamp();

        if ($seconds < 0) {
            return null;
        }

        return (float) $seconds;
    }

    private function averageSeconds(Collection $samples): ?float
    {
        $samples = $samples
            ->filter(fn ($sample) => is_numeric($sample))
            ->map(fn ($sample) => (float) $sample)
            ->values();

        if ($samples->isEmpty()) {
            return null;
        }

        return round((float) $samples->avg(), 1);
    }

    private function medianSeconds(Collection $samples): ?float
    {
        $values = $samples
            ->filter(fn ($sample) => is_numeric($sample))
            ->map(fn ($sample) => (float) $sample)
            ->sort()
            ->values();

        $count = $values->count();

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return round((($values[$middle - 1] + $values[$middle]) / 2), 1);
        }

        return round($values[$middle], 1);
    }

    private function metricTotal(
        CarbonInterface $from,
        CarbonInterface $to,
        string $dimensionType,
        string $metricKey,
    ): float {
        [$rangeStart, $rangeEnd] = $this->rangeBounds($from, $to);

        return (float) (DailyRollup::query()
            ->whereBetween('rollup_date', [$rangeStart, $rangeEnd])
            ->where('dimension_type', $dimensionType)
            ->where('metric_key', $metricKey)
            ->sum('metric_value') ?? 0);
    }

    private function safeRate(float $numerator, float $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 2);
    }

    private function conversionTypeLabel(int $conversionTypeId): string
    {
        $labels = collect(config('analytics.catalog.conversion_types', []))
            ->mapWithKeys(fn (int $id, string $key) => [$id => Str::headline(str_replace('_', ' ', $key))]);

        return $labels->get($conversionTypeId, "Conversion Type {$conversionTypeId}");
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rangeBounds(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            $from->copy()->startOfDay()->toDateTimeString(),
            $to->copy()->endOfDay()->toDateTimeString(),
        ];
    }
}
