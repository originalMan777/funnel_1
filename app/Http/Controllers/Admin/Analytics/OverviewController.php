<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Admin\Analytics\Concerns\ResolvesAnalyticsDateRange;
use App\Http\Controllers\Controller;
use App\Models\Analytics\AttributionTouch;
use App\Models\Analytics\Conversion;
use App\Models\Analytics\ConversionAttribution;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Services\Analytics\AnalyticsBootstrapService;
use App\Services\Analytics\AnalyticsFunnelService;
use App\Services\Analytics\AnalyticsHierarchyService;
use App\Services\Analytics\AnalyticsInterpretationService;
use App\Services\Analytics\AnalyticsNarrativeService;
use App\Services\Analytics\AnalyticsReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    use ResolvesAnalyticsDateRange;

    public function __construct(
        private readonly AnalyticsBootstrapService $analyticsBootstrapService,
        private readonly AnalyticsReportService $analyticsReportService,
        private readonly AnalyticsFunnelService $analyticsFunnelService,
        private readonly AnalyticsHierarchyService $analyticsHierarchyService,
        private readonly AnalyticsInterpretationService $analyticsInterpretationService,
        private readonly AnalyticsNarrativeService $analyticsNarrativeService,
    ) {}

    public function index(Request $request): Response
    {
        $ready = $this->tablesReady();
        [$from, $to] = $this->resolveRange($request, defaultDays: 30);
        $hasRollups = $ready && DailyRollup::query()->exists();
        $conversionSummary = $hasRollups ? $this->analyticsReportService->conversionSummary($from, $to) : ['by_type' => collect()];
        $scenarioSummary = $hasRollups ? $this->analyticsReportService->scenarioPerformance($from, $to) : collect();
        $attributionSummary = $ready ? $this->analyticsReportService->attributionSummary($from, $to) : ['overview' => ['attributed_conversions' => 0, 'unattributed_conversions' => 0], 'last_touch' => collect()];
        $funnels = $ready ? $this->analyticsFunnelService->analyze($from, $to) : collect();
        $interpretations = $ready ? $this->analyticsInterpretationService->summarize($from, $to) : collect();
        $clusters = $this->overviewClusters($from, $to);
        $summaryCards = $hasRollups
            ? $this->analyticsReportService->overviewSummary($from, $to)
            : [
                'page_views' => 0,
                'cta_clicks' => 0,
                'lead_form_submissions' => 0,
                'popup_submissions' => 0,
                'conversions' => 0,
                'average_session_duration_seconds' => null,
                'median_session_duration_seconds' => null,
                'average_time_to_conversion_seconds' => null,
                'median_time_to_conversion_seconds' => null,
            ];

        return Inertia::render('Admin/Analytics/Overview', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            'readiness' => [
                'enabled' => (bool) config('analytics.enabled'),
                'tables_ready' => $ready,
                'ingest_route' => route('analytics.ingest', absolute: false),
                'session_inactivity_timeout_minutes' => (int) config('analytics.session.inactivity_timeout_minutes', 30),
                'bootstrap_ready' => $this->analyticsBootstrapService->isReady(),
            ],
            'summary' => [
                'visitors' => $ready ? Visitor::query()->count() : 0,
                'sessions' => $ready ? Session::query()->count() : 0,
                'event_types' => $ready ? EventType::query()->count() : 0,
                'events' => $ready ? Event::query()->count() : 0,
                'attribution_touches' => $ready ? AttributionTouch::query()->count() : 0,
                'conversions' => $ready ? Conversion::query()->count() : 0,
                'conversion_attributions' => $ready && Schema::hasTable('analytics_conversion_attributions')
                    ? ConversionAttribution::query()->count()
                    : 0,
                'daily_rollups' => $ready ? DailyRollup::query()->count() : 0,
            ],
            'overview' => [
                'range' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'ready' => $hasRollups,
                'summary_cards' => $summaryCards,
                'trend' => $hasRollups
                    ? $this->analyticsReportService->overviewTrend($from, $to)->values()
                    : [],
                'conversion_types' => $hasRollups
                    ? $conversionSummary['by_type']->values()
                    : [],
                'top_scenarios' => $scenarioSummary->take(5)->values(),
                'top_funnels' => $funnels->take(3)->values(),
                'attribution' => [
                    'overview' => $attributionSummary['overview'],
                ],
                'interpretations' => $interpretations->values(),
            ],
            'overviewReport' => $this->analyticsNarrativeService->overviewReport($summaryCards),
            'analyticsClusters' => $clusters,
            'metricOccurrences' => $this->metricOccurrences($clusters),
        ]);
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('analytics_visitors')
            && Schema::hasTable('analytics_sessions')
            && Schema::hasTable('analytics_event_types')
            && Schema::hasTable('analytics_events')
            && Schema::hasTable('analytics_attribution_touches')
            && Schema::hasTable('analytics_conversions')
            && Schema::hasTable('analytics_daily_rollups')
            && Schema::hasTable('analytics_scenario_definitions')
            && Schema::hasTable('analytics_session_scenarios')
            && Schema::hasTable('analytics_conversion_attributions');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function overviewClusters($from, $to): array
    {
        return collect(['traffic', 'capture', 'flow', 'behavior', 'results', 'source'])
            ->map(fn (string $clusterKey) => $this->clusterPayload($clusterKey, $from, $to))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function clusterPayload(string $clusterKey, $from, $to): array
    {
        $payload = $this->analyticsHierarchyService->hydratedClusterPayload($clusterKey, $from, $to);

        return [
            ...$payload['cluster'],
            'href' => route('admin.analytics.clusters.show', [
                'clusterKey' => $clusterKey,
                ...$this->routeRangeParams($from, $to),
            ]),
            'clusterReport' => $this->analyticsNarrativeService->clusterReport($payload['cluster'], $payload['subClusters']),
            'subClusters' => $payload['subClusters'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $clusters
     * @return array<int, array<string, mixed>>
     */
    private function metricOccurrences(array $clusters): array
    {
        return collect($clusters)
            ->flatMap(function (array $cluster): Collection {
                return collect($cluster['subClusters'] ?? [])
                    ->flatMap(function (array $subCluster) use ($cluster): Collection {
                        return collect($subCluster['metricGroups'] ?? [])
                            ->flatMap(function (array $metricGroup) use ($cluster, $subCluster): Collection {
                                return collect($metricGroup['metrics'] ?? [])
                                    ->map(fn (array $metric): array => [
                                        ...$metric,
                                        'occurrenceKey' => implode(':', [
                                            $cluster['key'],
                                            $subCluster['key'],
                                            $metricGroup['key'],
                                            $metric['key'] ?? $metric['label'],
                                        ]),
                                        'cluster' => [
                                            'key' => $cluster['key'],
                                            'label' => $cluster['label'],
                                        ],
                                        'subCluster' => [
                                            'key' => $subCluster['key'],
                                            'label' => $subCluster['label'],
                                        ],
                                        'metricGroup' => [
                                            'key' => $metricGroup['key'],
                                            'label' => $metricGroup['label'],
                                            'detailHref' => $metricGroup['detailHref'] ?? null,
                                        ],
                                    ]);
                            });
                    });
            })
            ->values()
            ->all();
    }

    private function hydrateTrafficMetricValues(array $payload, $from, $to): array
    {
        $pageRows = $this->analyticsReportService->pagePerformance($from, $to)->values();

        if ($pageRows->isNotEmpty()) {
            $views = (float) $pageRows->sum('views');
            $conversions = (float) $pageRows->sum('conversions');

            $payload = $this->analyticsHierarchyService->withMetricValues($payload, 'pages', 'page_performance', [
                'views' => $this->metricOverride($views),
                'conversion_rate' => $this->percentMetricOverride($this->safePercent($conversions, $views)),
                'time_to_conversion' => $this->secondsMetricOverride($this->averageMetric($pageRows, 'avg_time_to_conversion_seconds')),
            ]);
        }

        $ctaRows = $this->analyticsReportService->ctaPerformance($from, $to)->values();

        if ($ctaRows->isNotEmpty()) {
            $impressions = (float) $ctaRows->sum('impressions');
            $clicks = (float) $ctaRows->sum('clicks');
            $conversions = (float) $ctaRows->sum('conversions');

            $payload = $this->analyticsHierarchyService->withMetricValues($payload, 'ctas', 'cta_performance', [
                'views' => $this->metricOverride($impressions),
                'clicks' => $this->metricOverride($clicks),
                'ctr' => $this->percentMetricOverride($this->safePercent($clicks, $impressions)),
                'conversion_rate' => $this->percentMetricOverride($this->safePercent($conversions, $clicks)),
                'time_to_conversion' => $this->secondsMetricOverride($this->averageMetric($ctaRows, 'avg_click_to_conversion_seconds')),
            ]);
        }

        return $payload;
    }

    private function hydrateCaptureMetricValues(array $payload, $from, $to): array
    {
        $leadBoxRows = $this->analyticsReportService->leadBoxPerformance($from, $to)->values();

        if ($leadBoxRows->isNotEmpty()) {
            $payload = $this->analyticsHierarchyService->withMetricValues($payload, 'lead_boxes', 'lead_box_lifecycle', [
                'views' => $this->metricOverride((float) $leadBoxRows->sum('impressions')),
                'clicks' => $this->metricOverride((float) $leadBoxRows->sum('clicks')),
                'submissions' => $this->metricOverride((float) $leadBoxRows->sum('submissions')),
                'failures' => $this->metricOverride((float) $leadBoxRows->sum('failures')),
                'duration' => $this->secondsMetricOverride($this->averageMetric($leadBoxRows, 'avg_impression_to_submit_seconds')),
            ]);
        }

        $popupRows = $this->analyticsReportService->popupPerformance($from, $to)->values();

        if ($popupRows->isNotEmpty()) {
            $impressions = (float) $popupRows->sum('impressions');
            $opens = (float) $popupRows->sum('opens');

            $payload = $this->analyticsHierarchyService->withMetricValues($payload, 'popups', 'popup_lifecycle', [
                'views' => $this->metricOverride($impressions),
                'open_rate' => $this->percentMetricOverride($this->safePercent($opens, $impressions)),
                'dismissals' => $this->metricOverride((float) $popupRows->sum('dismissals')),
                'submissions' => $this->metricOverride((float) $popupRows->sum('submissions')),
                'duration' => $this->secondsMetricOverride($this->averageMetric($popupRows, 'avg_open_to_submit_seconds')),
            ]);
        }

        return $payload;
    }

    private function hydrateFlowMetricValues(array $payload, $from, $to): array
    {
        $funnelRows = $this->analyticsFunnelService->analyze($from, $to)->values();
        $entrants = (float) $funnelRows->sum(fn (array $row) => (float) data_get($row, 'steps.0.count', 0));
        $conversions = (float) $funnelRows->sum(fn (array $row) => (float) ($row['conversion_count'] ?? 0));
        $dropOffs = $funnelRows
            ->map(fn (array $row) => data_get($row, 'top_drop_off.drop_off_to_next'))
            ->filter(fn ($value) => $value !== null)
            ->values();

        return $this->analyticsHierarchyService->withMetricValues($payload, 'funnels', 'funnel_performance', [
            'completion_rate' => $this->percentMetricOverride($this->safePercent($conversions, $entrants)),
            'drop_off' => $this->metricOverride($dropOffs->isNotEmpty() ? (float) $dropOffs->sum() : null),
            'duration' => $this->secondsMetricOverride($this->averageMetric($funnelRows, 'average_elapsed_seconds')),
        ]);
    }

    private function hydrateBehaviorMetricValues(array $payload, $from, $to): array
    {
        $scenarioRows = $this->analyticsReportService->scenarioPerformance($from, $to)->values();

        if ($scenarioRows->isEmpty()) {
            return $payload;
        }

        $sessions = (float) $scenarioRows->sum('sessions');
        $convertedSessions = (float) $scenarioRows->sum('converted_sessions');

        return $this->analyticsHierarchyService->withMetricValues($payload, 'scenarios', 'scenario_performance', [
            'views' => $this->metricOverride($sessions),
            'conversion_rate' => $this->percentMetricOverride($this->safePercent($convertedSessions, $sessions)),
            'duration' => $this->secondsMetricOverride($this->averageMetric($scenarioRows, 'average_session_duration_seconds')),
        ]);
    }

    private function hydrateResultsMetricValues(array $payload, $from, $to): array
    {
        $summary = $this->analyticsReportService->conversionSummary($from, $to);
        $totalRows = $summary['total']->values();

        if ($totalRows->isEmpty()) {
            return $payload;
        }

        return $this->analyticsHierarchyService->withMetricValues($payload, 'conversions', 'conversion_performance', [
            'submissions' => $this->metricOverride((float) $totalRows->sum('metric_value')),
            'time_to_conversion' => $this->secondsMetricOverride($summary['average_time_to_conversion_seconds']),
        ]);
    }

    private function hydrateSourceMetricValues(array $payload, $from, $to): array
    {
        $summary = $this->analyticsReportService->attributionSummary($from, $to);
        $attributedConversions = (float) ($summary['overview']['attributed_conversions'] ?? 0);
        $unattributedConversions = (float) ($summary['overview']['unattributed_conversions'] ?? 0);
        $totalConversions = $attributedConversions + $unattributedConversions;

        if ($totalConversions <= 0) {
            return $payload;
        }

        return $this->analyticsHierarchyService->withMetricValues($payload, 'attribution', 'attribution_performance', [
            'submissions' => $this->metricOverride($attributedConversions),
            'attribution_coverage' => $this->percentMetricOverride($this->safePercent($attributedConversions, $totalConversions)),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function routeRangeParams($from, $to): array
    {
        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }

    /**
     * @return array{value:string,displayValue:string}
     */
    private function metricOverride(null|int|float|string $value): array
    {
        $displayValue = $this->formatMetricDisplayValue($value);

        return [
            'value' => $displayValue,
            'displayValue' => $displayValue,
        ];
    }

    /**
     * @return array{value:string,displayValue:string}
     */
    private function percentMetricOverride(?float $value): array
    {
        return $this->metricOverride($value !== null ? number_format($value, 2).'%' : null);
    }

    /**
     * @return array{value:string,displayValue:string,helper:string}
     */
    private function secondsMetricOverride(?float $value): array
    {
        return [
            ...$this->metricOverride($value !== null ? round($value, 2) : null),
            'helper' => 'seconds',
        ];
    }

    private function formatMetricDisplayValue(null|int|float|string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_float($value) || is_int($value)) {
            return number_format($value, (float) $value === floor((float) $value) ? 0 : 2);
        }

        return (string) $value;
    }

    private function safePercent(float|int $numerator, float|int $denominator): ?float
    {
        return $denominator > 0 ? ((float) $numerator / (float) $denominator) * 100 : null;
    }

    private function averageMetric(Collection $rows, string $key): ?float
    {
        $values = $rows
            ->map(fn (array $row) => $row[$key] ?? null)
            ->filter(fn ($value) => $value !== null)
            ->values();

        return $values->isNotEmpty() ? (float) $values->avg() : null;
    }
}
