<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Admin\Analytics\Concerns\ResolvesAnalyticsDateRange;
use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsFunnelService;
use App\Services\Analytics\AnalyticsHierarchyService;
use App\Services\Analytics\AnalyticsNarrativeService;
use App\Services\Analytics\AnalyticsReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    use ResolvesAnalyticsDateRange;

    public function __construct(
        private readonly AnalyticsFunnelService $analyticsFunnelService,
        private readonly AnalyticsHierarchyService $analyticsHierarchyService,
        private readonly AnalyticsNarrativeService $analyticsNarrativeService,
        private readonly AnalyticsReportService $analyticsReportService,
    ) {}

    public function pages(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);

        return Inertia::render('Admin/Analytics/Pages', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            'report' => [
                'rows' => $this->analyticsReportService->pagePerformance($from, $to)->values(),
            ],
        ]);
    }

    public function metrics(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);

        return Inertia::render('Admin/Analytics/Metrics/Index', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
        ]);
    }

    public function graphicsLab(): Response
    {
        return Inertia::render('Admin/Analytics/GraphicsLab');
    }

    public function showCluster(Request $request, string $clusterKey): Response
    {
        [$from, $to] = $this->resolveRange($request);

        return Inertia::render('Admin/Analytics/Clusters/Show', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            ...$this->clusterPayload($clusterKey, $from, $to),
        ]);
    }

    public function showSubCluster(Request $request, string $clusterKey, string $subClusterKey): Response
    {
        [$from, $to] = $this->resolveRange($request);

        return Inertia::render('Admin/Analytics/SubClusters/Show', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            ...$this->subClusterPayload($clusterKey, $subClusterKey, $from, $to),
        ]);
    }

    public function showMetricGroup(
        Request $request,
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
    ): Response {
        [$from, $to] = $this->resolveRange($request);

        return Inertia::render('Admin/Analytics/MetricGroups/Show', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            ...$this->metricGroupPayload($clusterKey, $subClusterKey, $metricGroupKey, $from, $to),
        ]);
    }

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    private function clusterPayload(string $clusterKey, $from, $to): array
    {
        $payload = $this->analyticsHierarchyService->clusterPayload($clusterKey, $from, $to);

        $payload = match ($clusterKey) {
            'traffic' => $this->hydrateTrafficMetricValues($payload, $from, $to),
            'capture' => $this->hydrateCaptureMetricValues($payload, $from, $to),
            'flow' => $this->hydrateFlowMetricValues($payload, $from, $to),
            'behavior' => $this->hydrateBehaviorMetricValues($payload, $from, $to),
            'results' => $this->hydrateResultsMetricValues($payload, $from, $to),
            'source' => $this->hydrateSourceMetricValues($payload, $from, $to),
            default => abort(404),
        };

        return [
            ...$payload,
            'clusterReport' => $this->analyticsNarrativeService->clusterReport($payload['cluster'], $payload['subClusters']),
        ];
    }

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subCluster: array<string, mixed>,
     *     metricGroups: array<int, array<string, mixed>>
     * }
     */
    private function subClusterPayload(string $clusterKey, string $subClusterKey, $from, $to): array
    {
        $clusterPayload = $this->clusterPayload($clusterKey, $from, $to);
        $subCluster = collect($clusterPayload['subClusters'])
            ->firstWhere('key', $subClusterKey);

        if ($subCluster === null) {
            abort(404);
        }

        return [
            'cluster' => $clusterPayload['cluster'],
            'clusterReport' => $clusterPayload['clusterReport'],
            'subCluster' => $subCluster,
            'metricGroups' => $subCluster['metricGroups'],
        ];
    }

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subCluster: array<string, mixed>,
     *     metricGroup: array<string, mixed>,
     *     metrics: array<int, array<string, mixed>>
     * }
     */
    private function metricGroupPayload(
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
        $from,
        $to,
    ): array {
        $metricGroupKey = $this->analyticsHierarchyService->normalizeMetricGroupKey($metricGroupKey);
        $subClusterPayload = $this->subClusterPayload($clusterKey, $subClusterKey, $from, $to);
        $metricGroup = collect($subClusterPayload['metricGroups'])
            ->firstWhere('key', $metricGroupKey);

        if ($metricGroup === null) {
            abort(404);
        }

        return [
            'cluster' => $subClusterPayload['cluster'],
            'clusterReport' => $subClusterPayload['clusterReport'],
            'subCluster' => $subClusterPayload['subCluster'],
            'metricGroup' => $metricGroup,
            'metrics' => $metricGroup['metrics'],
        ];
    }

    /**
     * @param  array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }  $payload
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
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

    /**
     * @param  array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }  $payload
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
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

    /**
     * @param  array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }  $payload
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
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

    /**
     * @param  array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }  $payload
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
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

    /**
     * @param  array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }  $payload
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
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

    /**
     * @param  array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }  $payload
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
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

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    private function trafficClusterPayload($from, $to): array
    {
        return [
            'cluster' => [
                'key' => 'traffic',
                'label' => 'Traffic',
                'description' => 'Top-level analytics domain for audience movement and response.',
                'summaryShort' => 'Traffic summary will be generated from page and CTA movement.',
                'summaryFull' => 'Traffic summarizes movement across Pages and CTAs using the current flat analytics reports while future nested detail pages are still pending.',
            ],
            'subClusters' => [
                $this->pagesSubCluster($from, $to),
                $this->ctasSubCluster($from, $to),
            ],
        ];
    }

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    private function captureClusterPayload($from, $to): array
    {
        return [
            'cluster' => [
                'key' => 'capture',
                'label' => 'Lead Capture',
                'description' => 'Top-level analytics domain for lead capture surfaces.',
                'summaryShort' => 'Lead Capture summary will be generated from lead box and popup movement.',
                'summaryFull' => 'Lead Capture summarizes lead boxes and popups using the current flat analytics reports without introducing new detail routes yet.',
            ],
            'subClusters' => [
                $this->leadBoxesSubCluster($from, $to),
                $this->popupsSubCluster($from, $to),
            ],
        ];
    }

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    private function resultsClusterPayload($from, $to): array
    {
        $summary = $this->analyticsReportService->conversionSummary($from, $to);
        $conversionTypes = $summary['by_type']->values();
        $totalConversions = (float) array_sum(array_column($summary['total']->all(), 'metric_value'));

        $metricGroups = $conversionTypes->isNotEmpty()
            ? $conversionTypes
                ->map(function (array $row) use ($totalConversions, $from, $to): array {
                    $share = $totalConversions > 0
                        ? ((float) $row['total'] / $totalConversions) * 100
                        : null;

                    return [
                        'key' => 'conversion-type-'.$row['conversion_type_id'],
                        'label' => $row['label'],
                        'description' => 'Conversion type recorded in the current analytics conversion catalog.',
                        'detailHref' => $this->metricGroupRoute('results', 'conversions', 'conversion-type-'.$row['conversion_type_id'], $from, $to),
                        'metrics' => [
                            $this->metricValue(
                                'submissions',
                                'Total Conversions',
                                (string) $row['total'],
                                'Observed conversions recorded for this conversion type in the selected date range.',
                            ),
                            $this->metricValue(
                                'conversion_rate',
                                'Share of Conversions',
                                $share !== null ? number_format($share, 2).'%' : '—',
                                'Share of all conversions represented by this conversion type in the selected date range.',
                            ),
                        ],
                    ];
                })
                ->all()
            : [[
                'key' => 'overall',
                'label' => 'Overall Conversions',
                'description' => 'Aggregate conversion totals and event-based timing for the selected date range.',
                'detailHref' => $this->metricGroupRoute('results', 'conversions', 'overall', $from, $to),
                'metrics' => [
                    $this->metricValue(
                        'submissions',
                        'Total Conversions',
                        (string) $totalConversions,
                        'Observed conversions recorded across all conversion types in the selected date range.',
                    ),
                    $this->metricValue(
                        'time_to_conversion',
                        'Average Time to Conversion',
                        $summary['average_time_to_conversion_seconds'] !== null ? (string) $summary['average_time_to_conversion_seconds'] : '—',
                        'Average elapsed time from session start to first recorded conversion where timing is available.',
                        'seconds',
                    ),
                    $this->metricValue(
                        'time_to_conversion',
                        'Median Time to Conversion',
                        $summary['median_time_to_conversion_seconds'] !== null ? (string) $summary['median_time_to_conversion_seconds'] : '—',
                        'Median elapsed time from session start to first recorded conversion where timing is available.',
                        'seconds',
                    ),
                ],
            ]];

        return [
            'cluster' => [
                'key' => 'results',
                'label' => 'Conversions',
                'description' => 'Top-level analytics domain for outcome counts and completion results.',
                'summaryShort' => 'Conversions summary will be generated from conversion totals and timing movement.',
                'summaryFull' => 'Conversions will summarize conversion totals, type mix, and timing from the current flat conversions page before any nested detail pages are added.',
            ],
            'subClusters' => [
                [
                    'key' => 'conversions',
                    'label' => 'Conversions',
                    'description' => 'Sub-Cluster for conversion totals, type mix, and timing.',
                    'summaryShort' => 'Conversions summary will be generated from total conversions and conversion types.',
                    'summaryFull' => 'Conversions will summarize total movement, conversion type mix, and time-to-conversion signals from the current flat report.',
                    'href' => $this->subClusterRoute('results', 'conversions', $from, $to),
                    'flatHref' => $this->flatRoute('admin.analytics.conversions.index', $from, $to),
                    'metricGroups' => $metricGroups,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    private function sourceClusterPayload($from, $to): array
    {
        $summary = $this->analyticsReportService->attributionSummary($from, $to);
        $attributedConversions = (int) ($summary['overview']['attributed_conversions'] ?? 0);
        $unattributedConversions = (int) ($summary['overview']['unattributed_conversions'] ?? 0);
        $totalConversions = $attributedConversions + $unattributedConversions;

        $scopeMetricGroups = collect([
            'first_touch' => 'First-Touch',
            'last_touch' => 'Last-Touch',
            'conversion_touch' => 'Conversion-Touch',
        ])->map(function (string $label, string $scopeKey) use ($summary, $attributedConversions, $totalConversions): array {
            $rows = $summary[$scopeKey]->values();
            $scopeConversions = (int) $rows->sum('conversion_count');
            $shareOfAttributed = $attributedConversions > 0
                ? ($scopeConversions / $attributedConversions) * 100
                : null;
            $coverage = $totalConversions > 0
                ? ($scopeConversions / $totalConversions) * 100
                : null;
            $topSource = $rows->first();

            return [
                'key' => $scopeKey,
                'label' => $label,
                'description' => $topSource !== null
                    ? sprintf(
                        'Top source: %s via %s.',
                        $topSource['source_label'],
                        $topSource['attribution_method']
                    )
                    : 'No attribution rows found for this scope in the selected range.',
                'detailHref' => null,
                'metrics' => [
                    $this->metricValue(
                        'submissions',
                        'Attributed Conversions',
                        (string) $scopeConversions,
                        'Observed conversions attributed within this attribution scope.',
                    ),
                    $this->metricValue(
                        'conversion_rate',
                        'Share of Attributed',
                        $shareOfAttributed !== null ? number_format($shareOfAttributed, 2).'%' : '—',
                        'Share of attributed conversions represented by this scope in the selected date range.',
                    ),
                    $this->metricValue(
                        'attribution_coverage',
                        'Coverage',
                        $coverage !== null ? number_format($coverage, 2).'%' : '—',
                        'Share of all conversions covered by this attribution scope in the selected date range.',
                    ),
                    $this->metricValue(
                        'views',
                        'Tracked Sources',
                        (string) $rows->count(),
                        'Distinct tracked sources present inside this attribution scope in the selected date range.',
                    ),
                ],
            ];
        })->values()->all();

        return [
            'cluster' => [
                'key' => 'source',
                'label' => 'Source',
                'description' => 'Top-level analytics domain for attribution and source association.',
                'summaryShort' => 'Source summary will be generated from attribution coverage and source movement.',
                'summaryFull' => 'Source will summarize attribution coverage and top source associations using the current flat attribution page and existing attribution summaries.',
            ],
            'subClusters' => [
                [
                    'key' => 'attribution',
                    'label' => 'Attribution',
                    'description' => 'Sub-Cluster for attributed and unattributed conversion source summaries.',
                    'summaryShort' => 'Attribution summary will be generated from attributed and unattributed conversion movement.',
                    'summaryFull' => 'Attribution will summarize how observed sources appear across attribution scopes while preserving the current flat attribution route.',
                    'href' => $this->subClusterRoute('source', 'attribution', $from, $to),
                    'flatHref' => $this->flatRoute('admin.analytics.attribution.index', $from, $to),
                    'metricGroups' => collect($scopeMetricGroups)
                        ->map(fn (array $metricGroup) => [
                            ...$metricGroup,
                            'detailHref' => $this->metricGroupRoute('source', 'attribution', $metricGroup['key'], $from, $to),
                        ])
                        ->all(),
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    private function behaviorClusterPayload($from, $to): array
    {
        $scenarioRows = $this->analyticsReportService->scenarioPerformance($from, $to)->values();

        $metricGroups = $scenarioRows->isNotEmpty()
            ? $scenarioRows
                ->map(fn (array $row) => [
                    'key' => $row['scenario_key'],
                    'label' => $row['label'],
                    'description' => $row['description'] ?: $row['scenario_key'],
                    'detailHref' => $this->metricGroupRoute('behavior', 'scenarios', $row['scenario_key'], $from, $to),
                    'metrics' => [
                        $this->metricValue('views', 'Sessions', (string) $row['sessions'], 'Observed sessions assigned to this primary scenario in the selected date range.'),
                        $this->metricValue('submissions', 'Converted Sessions', (string) $row['converted_sessions'], 'Observed assigned sessions that produced at least one conversion.'),
                        $this->metricValue('submissions', 'Conversion Total', (string) $row['conversion_total'], 'Observed conversion count produced by assigned sessions in this scenario.'),
                        $this->metricValue('conversion_rate', 'Conversion Rate', $row['conversion_rate'] !== null ? number_format((float) $row['conversion_rate'], 2).'%' : '—', 'Share of assigned sessions that converted in the selected date range.'),
                        $this->metricValue('views', 'Average Events', (string) $row['average_events'], 'Average number of tracked analytics events recorded within sessions assigned to this scenario.'),
                        $this->metricValue('duration', 'Average Session Duration', $row['average_session_duration_seconds'] !== null ? (string) $row['average_session_duration_seconds'] : '—', 'Average event-based session duration for this scenario where timing is available.', 'seconds'),
                        $this->metricValue('duration', 'Median Session Duration', $row['median_session_duration_seconds'] !== null ? (string) $row['median_session_duration_seconds'] : '—', 'Median event-based session duration for this scenario where timing is available.', 'seconds'),
                    ],
                ])
                ->all()
            : [[
                'key' => 'overall',
                'label' => 'Overall Behavior',
                'description' => 'Aggregate behavior metrics across primary scenarios in the selected date range.',
                'detailHref' => $this->metricGroupRoute('behavior', 'scenarios', 'overall', $from, $to),
                'metrics' => [
                    $this->metricValue('views', 'Primary Scenarios', '0', 'Count of primary scenarios recorded in the selected date range.'),
                    $this->metricValue('views', 'Scenario Sessions', '0', 'Count of sessions assigned to primary scenarios in the selected date range.'),
                ],
            ]];

        return [
            'cluster' => [
                'key' => 'behavior',
                'label' => 'Behavior',
                'description' => 'Top-level analytics domain for observed session patterns.',
                'summaryShort' => 'Behavior summary will be generated from scenario assignment patterns.',
                'summaryFull' => 'Behavior will summarize primary and supporting session patterns derived from current scenario assignment logic without altering the existing flat page.',
            ],
            'subClusters' => [
                [
                    'key' => 'scenarios',
                    'label' => 'Scenarios',
                    'description' => 'Sub-Cluster for rule-based session scenarios and supporting patterns.',
                    'summaryShort' => 'Scenarios summary will be generated from scenario volume and conversion movement.',
                    'summaryFull' => 'Scenarios will summarize rule-based session patterns, their conversion movement, and representative sample journeys using the current flat report.',
                    'href' => $this->subClusterRoute('behavior', 'scenarios', $from, $to),
                    'flatHref' => $this->flatRoute('admin.analytics.scenarios.index', $from, $to),
                    'metricGroups' => $metricGroups,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    private function flowClusterPayload($from, $to): array
    {
        $funnelRows = $this->analyticsFunnelService->analyze($from, $to)->values();

        $metricGroups = $funnelRows->isNotEmpty()
            ? $funnelRows
                ->map(function (array $row) use ($from, $to): array {
                    $firstStepCount = (int) data_get($row, 'steps.0.count', 0);
                    $conversionCount = (int) ($row['conversion_count'] ?? 0);
                    $completionRate = $firstStepCount > 0
                        ? ($conversionCount / $firstStepCount) * 100
                        : null;
                    $topDropOff = $row['top_drop_off'] ?? null;
                    $metrics = [
                        $this->metricValue('submissions', 'Conversion Count', (string) $conversionCount, 'Observed conversions recorded inside this supported funnel in the selected date range.'),
                        $this->metricValue('completion_rate', 'Completion Rate', $completionRate !== null ? number_format($completionRate, 2).'%' : '—', 'Share of funnel entrants that reached the supported end state in the selected date range.'),
                        $this->metricValue('drop_off', 'Top Drop-Off Loss', $topDropOff !== null ? (string) $topDropOff['drop_off_to_next'] : '—', 'Largest observed step-to-step loss within this funnel in the selected date range.'),
                        $this->metricValue('duration', 'Average Elapsed', $row['average_elapsed_seconds'] !== null ? (string) $row['average_elapsed_seconds'] : '—', 'Average elapsed time across this supported funnel path where timing is available.', 'seconds'),
                        $this->metricValue('views', 'Step Count', (string) count($row['steps'] ?? []), 'Number of supported steps represented in this funnel definition.'),
                    ];

                    if (array_key_exists('dismissed_without_submit', $row)) {
                        $metrics[] = $this->metricValue(
                            'drop_off',
                            'Dismissed Without Submit',
                            (string) $row['dismissed_without_submit'],
                            'Observed popup dismissals that did not proceed to submission inside this funnel where supported.',
                        );
                    }

                    return [
                        'key' => $row['key'],
                        'label' => $row['label'],
                        'description' => $topDropOff !== null
                            ? sprintf(
                                'Top drop-off: %s lost %d sessions before the next step.',
                                $topDropOff['label'],
                                $topDropOff['drop_off_to_next']
                            )
                            : $row['description'],
                        'detailHref' => $this->metricGroupRoute('flow', 'funnels', $row['key'], $from, $to),
                        'metrics' => $metrics,
                    ];
                })
                ->all()
            : [[
                'key' => 'overall',
                'label' => 'Overall Flow',
                'description' => 'Aggregate funnel metrics for the selected date range.',
                'detailHref' => $this->metricGroupRoute('flow', 'funnels', 'overall', $from, $to),
                'metrics' => [
                    $this->metricValue('views', 'Funnels', '0', 'Count of supported funnels observed in the selected date range.'),
                    $this->metricValue('submissions', 'Conversion Count', '0', 'Observed conversions recorded across supported funnels in the selected date range.'),
                ],
            ]];

        return [
            'cluster' => [
                'key' => 'flow',
                'label' => 'Flow',
                'description' => 'Top-level analytics domain for reconstructed path progression.',
                'summaryShort' => 'Flow summary will be generated from funnel progression and drop-off.',
                'summaryFull' => 'Flow will summarize supported funnel progression, completion, and leakage using the existing funnel analysis service and current flat page.',
            ],
            'subClusters' => [
                [
                    'key' => 'funnels',
                    'label' => 'Funnels',
                    'description' => 'Sub-Cluster for supported session funnels and their progression steps.',
                    'summaryShort' => 'Funnels summary will be generated from supported step progression.',
                    'summaryFull' => 'Funnels will summarize supported step progression, completion, and drop-off from the current session-based funnel analysis output.',
                    'href' => $this->subClusterRoute('flow', 'funnels', $from, $to),
                    'flatHref' => $this->flatRoute('admin.analytics.funnels.index', $from, $to),
                    'metricGroups' => $metricGroups,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pagesSubCluster($from, $to): array
    {
        $metricGroups = $this->analyticsReportService->pagePerformance($from, $to)
            ->values()
            ->map(fn (array $row) => [
                'key' => $row['key'],
                'label' => $row['label'],
                'description' => $row['category'] ?: $row['key'],
                'detailHref' => $this->metricGroupRoute('traffic', 'pages', $row['key'], $from, $to),
                'metrics' => [
                    $this->metricValue('views', 'Views', (string) $row['views'], 'Observed page views attributed to this page in the selected date range.'),
                    $this->metricValue('submissions', 'Conversions', (string) $row['conversions'], 'Observed conversions associated with this page in the selected date range.'),
                    $this->metricValue('conversion_rate', 'Conversion Rate', $row['conversion_rate'] !== null ? number_format((float) $row['conversion_rate'], 2).'%' : '—', 'Share of page views that later produced a conversion in the selected date range.'),
                    $this->metricValue('time_to_conversion', 'Avg View to Conversion', $row['avg_time_to_conversion_seconds'] !== null ? (string) $row['avg_time_to_conversion_seconds'] : '—', 'Average elapsed time from page interaction to conversion where timing is available.', 'seconds'),
                ],
            ])
            ->all();

        return [
            'key' => 'pages',
            'label' => 'Pages',
            'description' => 'Sub-Cluster for tracked page performance and page-level movement.',
            'summaryShort' => 'Pages summary will be generated from tracked page views and conversions.',
            'summaryFull' => 'Pages will anchor the Traffic cluster with tracked page performance, conversion totals, and timing signals from the current flat analytics report.',
            'href' => $this->subClusterRoute('traffic', 'pages', $from, $to),
            'flatHref' => $this->flatRoute('admin.analytics.pages.index', $from, $to),
            'metricGroups' => $metricGroups,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ctasSubCluster($from, $to): array
    {
        $metricGroups = $this->analyticsReportService->ctaPerformance($from, $to)
            ->values()
            ->map(fn (array $row) => [
                'key' => $row['key'],
                'label' => $row['label'],
                'description' => $row['intent_key'] ?: $row['key'],
                'detailHref' => $this->metricGroupRoute('traffic', 'ctas', $row['key'], $from, $to),
                'metrics' => [
                    $this->metricValue('clicks', 'Clicks', (string) $row['clicks'], 'Observed CTA clicks attributed to this CTA in the selected date range.'),
                    $this->metricValue('submissions', 'Conversions', (string) $row['conversions'], 'Observed conversions attributed to this CTA in the selected date range.'),
                    $this->metricValue('ctr', 'CTR', $row['ctr'] !== null ? number_format((float) $row['ctr'], 2).'%' : '—', 'Click-through rate for this CTA in the selected date range.'),
                    $this->metricValue('conversion_rate', 'Conversion Rate', $row['conversion_rate'] !== null ? number_format((float) $row['conversion_rate'], 2).'%' : '—', 'Share of CTA clicks that later produced a conversion in the selected date range.'),
                    $this->metricValue('duration', 'Avg Time to Click', $row['avg_time_to_click_seconds'] !== null ? (string) $row['avg_time_to_click_seconds'] : '—', 'Average elapsed time from CTA impression to CTA click where timing is available.', 'seconds'),
                ],
            ])
            ->all();

        return [
            'key' => 'ctas',
            'label' => 'CTAs',
            'description' => 'Sub-Cluster for tracked CTA visibility, clicks, and follow-through.',
            'summaryShort' => 'CTAs summary will be generated from impressions, clicks, and conversion movement.',
            'summaryFull' => 'CTAs will summarize how tracked calls to action are seen, clicked, and carried forward into later conversions using the existing flat report.',
            'href' => $this->subClusterRoute('traffic', 'ctas', $from, $to),
            'flatHref' => $this->flatRoute('admin.analytics.ctas.index', $from, $to),
            'metricGroups' => $metricGroups,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leadBoxesSubCluster($from, $to): array
    {
        $metricGroups = $this->analyticsReportService->leadBoxPerformance($from, $to)
            ->values()
            ->map(fn (array $row) => [
                'key' => $row['key'],
                'label' => $row['label'],
                'description' => $row['type'] ?: $row['key'],
                'detailHref' => $this->metricGroupRoute('capture', 'lead_boxes', $row['key'], $from, $to),
                'metrics' => [
                    $this->metricValue('views', 'Impressions', (string) $row['impressions'], 'Observed lead box impressions for this lead box in the selected date range.'),
                    $this->metricValue('clicks', 'Clicks', (string) $row['clicks'], 'Observed lead box clicks for this lead box in the selected date range.'),
                    $this->metricValue('submissions', 'Submissions', (string) $row['submissions'], 'Observed lead form submissions attributed to this lead box in the selected date range.'),
                    $this->metricValue('failures', 'Failures', (string) $row['failures'], 'Observed lead form failures attributed to this lead box in the selected date range.'),
                    $this->metricValue('conversion_rate', 'Submission Rate', $row['submission_rate'] !== null ? number_format((float) $row['submission_rate'], 2).'%' : '—', 'Share of lead box clicks that ended in a successful submission.'),
                ],
            ])
            ->all();

        return [
            'key' => 'lead_boxes',
            'label' => 'Lead Boxes',
            'description' => 'Sub-Cluster for tracked lead box exposure, engagement, and submission outcomes.',
            'summaryShort' => 'Lead Boxes summary will be generated from impressions, clicks, and submissions.',
            'summaryFull' => 'Lead Boxes will summarize lead capture surface performance using the current flat report for exposure, submission, and failure movement.',
            'href' => $this->subClusterRoute('capture', 'lead_boxes', $from, $to),
            'flatHref' => $this->flatRoute('admin.analytics.lead-boxes.index', $from, $to),
            'metricGroups' => $metricGroups,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function popupsSubCluster($from, $to): array
    {
        $metricGroups = $this->analyticsReportService->popupPerformance($from, $to)
            ->values()
            ->map(fn (array $row) => [
                'key' => $row['key'],
                'label' => $row['label'],
                'description' => $row['type'] ?: $row['key'],
                'detailHref' => $this->metricGroupRoute('capture', 'popups', $row['key'], $from, $to),
                'metrics' => [
                    $this->metricValue('views', 'Impressions', (string) $row['impressions'], 'Observed popup impressions for this popup in the selected date range.'),
                    $this->metricValue('open_rate', 'Open Rate', $row['open_rate'] !== null ? number_format((float) $row['open_rate'], 2).'%' : '—', 'Share of popup impressions that progressed into opens.'),
                    $this->metricValue('dismissals', 'Dismissals', (string) $row['dismissals'], 'Observed popup dismissals recorded for this popup in the selected date range.'),
                    $this->metricValue('submissions', 'Submissions', (string) $row['submissions'], 'Observed popup submissions recorded for this popup in the selected date range.'),
                    $this->metricValue('duration', 'Avg Open to Submit', $row['avg_open_to_submit_seconds'] !== null ? (string) $row['avg_open_to_submit_seconds'] : '—', 'Average elapsed time from popup open to popup submission where timing is available.', 'seconds'),
                ],
            ])
            ->all();

        return [
            'key' => 'popups',
            'label' => 'Popups',
            'description' => 'Sub-Cluster for tracked popup eligibility, opens, dismissals, and submissions.',
            'summaryShort' => 'Popups summary will be generated from popup lifecycle movement.',
            'summaryFull' => 'Popups will summarize the popup lifecycle from eligibility through opens, dismissals, and submissions using the current flat report.',
            'href' => $this->subClusterRoute('capture', 'popups', $from, $to),
            'flatHref' => $this->flatRoute('admin.analytics.popups.index', $from, $to),
            'metricGroups' => $metricGroups,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metricValue(
        string $key,
        string $label,
        string $value,
        string $description,
        ?string $helper = null,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'description' => $description,
            'helper' => $helper,
        ];
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

    private function flatRoute(string $routeName, $from, $to): string
    {
        return route($routeName, $this->routeRangeParams($from, $to));
    }

    private function subClusterRoute(string $clusterKey, string $subClusterKey, $from, $to): string
    {
        return route('admin.analytics.sub-clusters.show', [
            'clusterKey' => $clusterKey,
            'subClusterKey' => $subClusterKey,
            ...$this->routeRangeParams($from, $to),
        ]);
    }

    private function metricGroupRoute(
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
        $from,
        $to,
    ): string {
        return route('admin.analytics.metric-groups.show', [
            'clusterKey' => $clusterKey,
            'subClusterKey' => $subClusterKey,
            'metricGroupKey' => $metricGroupKey,
            ...$this->routeRangeParams($from, $to),
        ]);
    }

    public function ctas(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);

        return Inertia::render('Admin/Analytics/Ctas', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            'report' => [
                'rows' => $this->analyticsReportService->ctaPerformance($from, $to)->values(),
                'trend' => $this->analyticsReportService->ctaTrend($from, $to)->values(),
            ],
        ]);
    }

    public function leadBoxes(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);

        return Inertia::render('Admin/Analytics/LeadBoxes', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            'report' => [
                'rows' => $this->analyticsReportService->leadBoxPerformance($from, $to)->values(),
            ],
        ]);
    }

    public function popups(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);

        return Inertia::render('Admin/Analytics/Popups', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            'report' => [
                'rows' => $this->analyticsReportService->popupPerformance($from, $to)->values(),
            ],
        ]);
    }

    public function conversions(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $summary = $this->analyticsReportService->conversionSummary($from, $to);

        return Inertia::render('Admin/Analytics/Conversions', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            'report' => [
                'total' => array_sum(array_column($summary['total']->all(), 'metric_value')),
                'trend' => $this->analyticsReportService->conversionTrend($from, $to)->values(),
                'conversion_types' => $summary['by_type']->values(),
                'average_time_to_conversion_seconds' => $summary['average_time_to_conversion_seconds'],
                'median_time_to_conversion_seconds' => $summary['median_time_to_conversion_seconds'],
            ],
        ]);
    }

    public function attribution(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $summary = $this->analyticsReportService->attributionSummary($from, $to);

        return Inertia::render('Admin/Analytics/Attribution', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            'report' => [
                'overview' => $summary['overview'],
                'first_touch' => $summary['first_touch']->values(),
                'last_touch' => $summary['last_touch']->values(),
                'conversion_touch' => $summary['conversion_touch']->values(),
            ],
        ]);
    }
}
