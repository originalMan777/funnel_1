<?php

namespace App\Services\Analytics;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Throwable;

class AnalyticsHierarchyService
{
    public function __construct(
        private readonly AnalyticsDemoMetricValueService $demoMetricValueService,
        private readonly AnalyticsNarrativeService $analyticsNarrativeService,
        private readonly AnalyticsFunnelService $analyticsFunnelService,
        private readonly AnalyticsReportService $analyticsReportService,
    ) {}

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    public function clusterPayload(string $clusterKey, CarbonInterface $from, CarbonInterface $to): array
    {
        $cluster = $this->clusterDefinition($clusterKey);

        abort_if($cluster === null, 404);

        return [
            'cluster' => Arr::except($cluster, ['subClusters']),
            'subClusters' => collect($cluster['subClusters'])
                ->map(fn (array $subCluster) => $this->materializeSubCluster($cluster['key'], $subCluster, $from, $to))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    public function hydratedClusterPayload(string $clusterKey, CarbonInterface $from, CarbonInterface $to): array
    {
        $payload = $this->clusterPayload($clusterKey, $from, $to);

        try {
            return match ($clusterKey) {
                'traffic' => $this->hydrateTrafficMetricValues($payload, $from, $to),
                'capture' => $this->hydrateCaptureMetricValues($payload, $from, $to),
                'flow' => $this->hydrateFlowMetricValues($payload, $from, $to),
                'behavior' => $this->hydrateBehaviorMetricValues($payload, $from, $to),
                'results' => $this->hydrateResultsMetricValues($payload, $from, $to),
                'source' => $this->hydrateSourceMetricValues($payload, $from, $to),
                default => abort(404),
            };
        } catch (Throwable) {
            return $payload;
        }
    }

    public function normalizeMetricGroupKey(string $metricGroupKey): string
    {
        return str_replace('-', '_', $metricGroupKey);
    }

    /**
     * @param  array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }  $payload
     * @param  array<string, array<string, mixed>>  $metricValues
     * @return array{
     *     cluster: array<string, mixed>,
     *     subClusters: array<int, array<string, mixed>>
     * }
     */
    public function withMetricValues(
        array $payload,
        string $subClusterKey,
        string $metricGroupKey,
        array $metricValues,
    ): array {
        $payload['subClusters'] = collect($payload['subClusters'])
            ->map(function (array $subCluster) use ($subClusterKey, $metricGroupKey, $metricValues): array {
                if ($subCluster['key'] !== $subClusterKey) {
                    return $subCluster;
                }

                $subCluster['metricGroups'] = collect($subCluster['metricGroups'])
                    ->map(function (array $metricGroup) use ($subCluster, $metricGroupKey, $metricValues): array {
                        if ($metricGroup['key'] !== $metricGroupKey) {
                            return $metricGroup;
                        }

                        $metricGroup['metrics'] = collect($metricGroup['metrics'])
                            ->map(function (array $metric) use ($subCluster, $metricGroup, $metricValues): array {
                                $override = $metricValues[$metric['key']] ?? null;

                                if ($override === null) {
                                    return $metric;
                                }

                                $value = $override['value'] ?? $metric['value'];
                                $displayValue = $override['displayValue'] ?? $value;

                                if ($this->isEmptyMetricValue($value) && ($metric['dataSource'] ?? null) === 'local_demo') {
                                    return $metric;
                                }

                                $overriddenMetric = [
                                    ...$metric,
                                    ...Arr::except($override, ['value']),
                                    'value' => $value,
                                    'displayValue' => $displayValue,
                                    'dataSource' => 'real',
                                    'parsedData' => ! $this->isEmptyMetricValue($displayValue),
                                ];

                                return [
                                    ...$overriddenMetric,
                                    ...$this->demoMetricValueService->interpretationFor(
                                        $subCluster['clusterKey'],
                                        $subCluster['key'],
                                        $metricGroup['key'],
                                        $metric['key'],
                                        $overriddenMetric,
                                    ),
                                ];
                            })
                            ->values()
                            ->all();

                        return [
                            ...$metricGroup,
                            'groupReport' => $this->analyticsNarrativeService->groupReport($metricGroup),
                        ];
                    })
                    ->values()
                    ->all();

                return $subCluster;
            })
            ->values()
            ->all();

        return $payload;
    }

    private function hydrateTrafficMetricValues(array $payload, CarbonInterface $from, CarbonInterface $to): array
    {
        $pageRows = $this->analyticsReportService->pagePerformance($from, $to)->values();

        if ($pageRows->isNotEmpty()) {
            $views = (float) $pageRows->sum('views');
            $conversions = (float) $pageRows->sum('conversions');

            $payload = $this->withMetricValues($payload, 'pages', 'page_performance', [
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

            $payload = $this->withMetricValues($payload, 'ctas', 'cta_performance', [
                'views' => $this->metricOverride($impressions),
                'clicks' => $this->metricOverride($clicks),
                'ctr' => $this->percentMetricOverride($this->safePercent($clicks, $impressions)),
                'conversion_rate' => $this->percentMetricOverride($this->safePercent($conversions, $clicks)),
                'time_to_conversion' => $this->secondsMetricOverride($this->averageMetric($ctaRows, 'avg_click_to_conversion_seconds')),
            ]);
        }

        return $payload;
    }

    private function hydrateCaptureMetricValues(array $payload, CarbonInterface $from, CarbonInterface $to): array
    {
        $leadBoxRows = $this->analyticsReportService->leadBoxPerformance($from, $to)->values();

        if ($leadBoxRows->isNotEmpty()) {
            $payload = $this->withMetricValues($payload, 'lead_boxes', 'lead_box_lifecycle', [
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

            $payload = $this->withMetricValues($payload, 'popups', 'popup_lifecycle', [
                'views' => $this->metricOverride($impressions),
                'open_rate' => $this->percentMetricOverride($this->safePercent($opens, $impressions)),
                'dismissals' => $this->metricOverride((float) $popupRows->sum('dismissals')),
                'submissions' => $this->metricOverride((float) $popupRows->sum('submissions')),
                'duration' => $this->secondsMetricOverride($this->averageMetric($popupRows, 'avg_open_to_submit_seconds')),
            ]);
        }

        return $payload;
    }

    private function hydrateFlowMetricValues(array $payload, CarbonInterface $from, CarbonInterface $to): array
    {
        $funnelRows = $this->analyticsFunnelService->analyze($from, $to)->values();
        $entrants = (float) $funnelRows->sum(fn (array $row) => (float) data_get($row, 'steps.0.count', 0));
        $conversions = (float) $funnelRows->sum(fn (array $row) => (float) ($row['conversion_count'] ?? 0));
        $dropOffs = $funnelRows
            ->map(fn (array $row) => data_get($row, 'top_drop_off.drop_off_to_next'))
            ->filter(fn ($value) => $value !== null)
            ->values();

        return $this->withMetricValues($payload, 'funnels', 'funnel_performance', [
            'completion_rate' => $this->percentMetricOverride($this->safePercent($conversions, $entrants)),
            'drop_off' => $this->metricOverride($dropOffs->isNotEmpty() ? (float) $dropOffs->sum() : null),
            'duration' => $this->secondsMetricOverride($this->averageMetric($funnelRows, 'average_elapsed_seconds')),
        ]);
    }

    private function hydrateBehaviorMetricValues(array $payload, CarbonInterface $from, CarbonInterface $to): array
    {
        $scenarioRows = $this->analyticsReportService->scenarioPerformance($from, $to)->values();

        if ($scenarioRows->isEmpty()) {
            return $payload;
        }

        $sessions = (float) $scenarioRows->sum('sessions');
        $convertedSessions = (float) $scenarioRows->sum('converted_sessions');

        return $this->withMetricValues($payload, 'scenarios', 'scenario_performance', [
            'views' => $this->metricOverride($sessions),
            'conversion_rate' => $this->percentMetricOverride($this->safePercent($convertedSessions, $sessions)),
            'duration' => $this->secondsMetricOverride($this->averageMetric($scenarioRows, 'average_session_duration_seconds')),
        ]);
    }

    private function hydrateResultsMetricValues(array $payload, CarbonInterface $from, CarbonInterface $to): array
    {
        $summary = $this->analyticsReportService->conversionSummary($from, $to);
        $totalRows = $summary['total']->values();

        if ($totalRows->isEmpty()) {
            return $payload;
        }

        return $this->withMetricValues($payload, 'conversions', 'conversion_performance', [
            'submissions' => $this->metricOverride((float) $totalRows->sum('metric_value')),
            'time_to_conversion' => $this->secondsMetricOverride($summary['average_time_to_conversion_seconds']),
        ]);
    }

    private function hydrateSourceMetricValues(array $payload, CarbonInterface $from, CarbonInterface $to): array
    {
        $summary = $this->analyticsReportService->attributionSummary($from, $to);
        $attributedConversions = (float) ($summary['overview']['attributed_conversions'] ?? 0);
        $unattributedConversions = (float) ($summary['overview']['unattributed_conversions'] ?? 0);
        $totalConversions = $attributedConversions + $unattributedConversions;

        if ($totalConversions <= 0) {
            return $payload;
        }

        return $this->withMetricValues($payload, 'attribution', 'attribution_performance', [
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
     * @return array<string, mixed>|null
     */
    private function clusterDefinition(string $clusterKey): ?array
    {
        return collect($this->definitions())->firstWhere('key', $clusterKey);
    }

    /**
     * @return array<string, mixed>
     */
    private function materializeSubCluster(string $clusterKey, array $subCluster, CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            ...Arr::except($subCluster, ['metricGroups', 'flatRouteName']),
            'clusterKey' => $clusterKey,
            'href' => route('admin.analytics.sub-clusters.show', [
                'clusterKey' => $clusterKey,
                'subClusterKey' => $subCluster['key'],
                ...$this->routeRangeParams($from, $to),
            ]),
            'flatHref' => route($subCluster['flatRouteName'], $this->routeRangeParams($from, $to)),
            'metricGroups' => collect($subCluster['metricGroups'])
                ->map(fn (array $metricGroup) => [
                    ...$metricGroup,
                    'detailHref' => route('admin.analytics.metric-groups.show', [
                        'clusterKey' => $clusterKey,
                        'subClusterKey' => $subCluster['key'],
                        'metricGroupKey' => $metricGroup['key'],
                        ...$this->routeRangeParams($from, $to),
                    ]),
                    'metrics' => collect($metricGroup['metrics'])
                        ->map(fn (array $metric) => $this->demoMetric($clusterKey, $subCluster['key'], $metricGroup['key'], $metric))
                        ->values()
                        ->all(),
                ])
                ->map(fn (array $metricGroup) => [
                    ...$metricGroup,
                    'groupReport' => $this->analyticsNarrativeService->groupReport($metricGroup),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function demoMetric(string $clusterKey, string $subClusterKey, string $metricGroupKey, array $metric): array
    {
        $demoValue = $this->demoMetricValueService->fallbackFor(
            $clusterKey,
            $subClusterKey,
            $metricGroupKey,
            $metric['key'],
        );

        return [
            ...$metric,
            ...$demoValue,
            ...$this->demoMetricValueService->definitionFor(
                $clusterKey,
                $subClusterKey,
                $metricGroupKey,
                $metric['key'],
            ),
            'approvedVisualKey' => $this->approvedVisualKey($clusterKey, $subClusterKey, $metricGroupKey, $metric['key']),
            'helper' => $metric['helper'] ?? null,
        ];
    }

    private function approvedVisualKey(
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
        string $metricKey,
    ): ?string {
        return [
            'traffic|ctas|cta_performance|ctr' => 'half-ring-score',
            'traffic|pages|page_performance|conversion_rate' => 'half-ring-score',
            'traffic|ctas|cta_performance|conversion_rate' => 'half-ring-score',
            'capture|popups|popup_lifecycle|open_rate' => 'half-ring-score',
            'flow|funnels|funnel_performance|completion_rate' => 'funnel-flow',
            'flow|funnels|funnel_performance|drop_off' => 'funnel-flow',
            'traffic|pages|page_performance|time_to_conversion' => 'range-variance',
            'traffic|ctas|cta_performance|time_to_conversion' => 'range-variance',
            'results|conversions|conversion_performance|time_to_conversion' => 'range-variance',
            'source|attribution|attribution_performance|attribution_coverage' => 'premium-donut',
            'traffic|pages|page_performance|views' => 'premium-vertical-bar',
            'traffic|ctas|cta_performance|views' => 'premium-vertical-bar',
            'traffic|ctas|cta_performance|clicks' => 'premium-vertical-bar',
            'capture|lead_boxes|lead_box_lifecycle|views' => 'mini-report-card',
            'capture|lead_boxes|lead_box_lifecycle|clicks' => 'mini-report-card',
            'capture|lead_boxes|lead_box_lifecycle|submissions' => 'mini-report-card',
            'capture|popups|popup_lifecycle|views' => 'mini-report-card',
            'capture|popups|popup_lifecycle|submissions' => 'mini-report-card',
            'capture|popups|popup_lifecycle|dismissals' => 'mini-report-card',
            'results|conversions|conversion_performance|submissions' => 'premium-donut',
            'capture|lead_boxes|lead_box_lifecycle|failures' => 'mini-report-card',
            'capture|lead_boxes|lead_box_lifecycle|duration' => 'range-variance',
            'capture|popups|popup_lifecycle|duration' => 'range-variance',
            'flow|funnels|funnel_performance|duration' => 'range-variance',
            'behavior|scenarios|scenario_performance|views' => 'stacked-performance',
            'behavior|scenarios|scenario_performance|conversion_rate' => 'stacked-performance',
            'behavior|scenarios|scenario_performance|duration' => 'range-variance',
            'source|attribution|attribution_performance|submissions' => 'source-ranking-table',
        ][$this->contextKey($clusterKey, $subClusterKey, $metricGroupKey, $metricKey)] ?? null;
    }

    private function contextKey(
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
        string $metricKey,
    ): string {
        return implode('|', [$clusterKey, $subClusterKey, $metricGroupKey, $metricKey]);
    }

    private function isEmptyMetricValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === '—';
    }

    /**
     * @return array<string, string>
     */
    private function routeRangeParams(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            [
                'key' => 'traffic',
                'label' => 'Traffic',
                'description' => 'Top-level analytics domain for audience movement and response.',
                'summaryShort' => 'Traffic summary will be generated from page and CTA movement.',
                'summaryFull' => 'Traffic summarizes movement across Pages and CTAs using the current flat analytics reports while future nested detail pages are still pending.',
                'subClusters' => [
                    [
                        'key' => 'pages',
                        'label' => 'Pages',
                        'description' => 'Sub-Cluster for tracked page performance and page-level movement.',
                        'summaryShort' => 'Pages summary will be generated from tracked page views and conversions.',
                        'summaryFull' => 'Pages will anchor the Traffic cluster with tracked page performance, conversion totals, and timing signals from the current flat analytics report.',
                        'flatRouteName' => 'admin.analytics.pages.index',
                        'metricGroups' => [
                            [
                                'key' => 'page_performance',
                                'label' => 'Page Performance',
                                'description' => 'Page-level reach, conversion quality, and conversion timing.',
                                'metrics' => [
                                    ['key' => 'views', 'label' => 'Views', 'description' => 'Observed page views attributed to the selected range.'],
                                    ['key' => 'conversion_rate', 'label' => 'Conversion Rate', 'description' => 'Share of page views that later produced a conversion.'],
                                    ['key' => 'time_to_conversion', 'label' => 'Time to Conversion', 'description' => 'Elapsed time from page interaction to conversion where supported.', 'helper' => 'seconds'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'ctas',
                        'label' => 'CTAs',
                        'description' => 'Sub-Cluster for tracked CTA visibility, clicks, and follow-through.',
                        'summaryShort' => 'CTAs summary will be generated from impressions, clicks, and conversion movement.',
                        'summaryFull' => 'CTAs will summarize how tracked calls to action are seen, clicked, and carried forward into later conversions using the existing flat report.',
                        'flatRouteName' => 'admin.analytics.ctas.index',
                        'metricGroups' => [
                            [
                                'key' => 'cta_performance',
                                'label' => 'CTA Performance',
                                'description' => 'CTA exposure, engagement, click-through, conversion quality, and timing.',
                                'metrics' => [
                                    ['key' => 'views', 'label' => 'Impressions', 'description' => 'Observed CTA impressions for the selected range.'],
                                    ['key' => 'clicks', 'label' => 'Clicks', 'description' => 'Observed CTA clicks for the selected range.'],
                                    ['key' => 'ctr', 'label' => 'CTR', 'description' => 'Click-through rate for CTA impressions.'],
                                    ['key' => 'conversion_rate', 'label' => 'Conversion Rate', 'description' => 'Share of CTA clicks that later produced a conversion.'],
                                    ['key' => 'time_to_conversion', 'label' => 'Time to Conversion', 'description' => 'Elapsed time from CTA interaction to later conversion where supported.', 'helper' => 'seconds'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'capture',
                'label' => 'Lead Capture',
                'description' => 'Top-level analytics domain for lead capture surfaces.',
                'summaryShort' => 'Lead Capture summary will be generated from lead box and popup movement.',
                'summaryFull' => 'Lead Capture summarizes lead boxes and popups using the current flat analytics reports without introducing new detail routes yet.',
                'subClusters' => [
                    [
                        'key' => 'lead_boxes',
                        'label' => 'Lead Boxes',
                        'description' => 'Sub-Cluster for tracked lead box exposure, engagement, and submission outcomes.',
                        'summaryShort' => 'Lead Boxes summary will be generated from impressions, clicks, and submissions.',
                        'summaryFull' => 'Lead Boxes will summarize lead capture surface performance using the current flat report for exposure, submission, and failure movement.',
                        'flatRouteName' => 'admin.analytics.lead-boxes.index',
                        'metricGroups' => [
                            [
                                'key' => 'lead_box_lifecycle',
                                'label' => 'Lead Box Lifecycle',
                                'description' => 'Lead box exposure, engagement, submissions, failures, and path duration.',
                                'metrics' => [
                                    ['key' => 'views', 'label' => 'Impressions', 'description' => 'Observed lead box impressions for the selected range.'],
                                    ['key' => 'clicks', 'label' => 'Clicks', 'description' => 'Observed lead box clicks for the selected range.'],
                                    ['key' => 'submissions', 'label' => 'Submissions', 'description' => 'Observed lead box submissions for the selected range.'],
                                    ['key' => 'failures', 'label' => 'Failures', 'description' => 'Observed lead form failures for the selected range.'],
                                    ['key' => 'duration', 'label' => 'Duration', 'description' => 'Elapsed time through the lead box submission path where supported.', 'helper' => 'seconds'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'popups',
                        'label' => 'Popups',
                        'description' => 'Sub-Cluster for tracked popup eligibility, opens, dismissals, and submissions.',
                        'summaryShort' => 'Popups summary will be generated from popup lifecycle movement.',
                        'summaryFull' => 'Popups will summarize the popup lifecycle from eligibility through opens, dismissals, and submissions using the current flat report.',
                        'flatRouteName' => 'admin.analytics.popups.index',
                        'metricGroups' => [
                            [
                                'key' => 'popup_lifecycle',
                                'label' => 'Popup Lifecycle',
                                'description' => 'Popup impressions, open rate, dismissals, submissions, and lifecycle duration.',
                                'metrics' => [
                                    ['key' => 'views', 'label' => 'Impressions', 'description' => 'Observed popup impressions for the selected range.'],
                                    ['key' => 'open_rate', 'label' => 'Open Rate', 'description' => 'Share of popup impressions that progressed into opens.'],
                                    ['key' => 'dismissals', 'label' => 'Dismissals', 'description' => 'Observed popup dismissals for the selected range.'],
                                    ['key' => 'submissions', 'label' => 'Submissions', 'description' => 'Observed popup submissions for the selected range.'],
                                    ['key' => 'duration', 'label' => 'Duration', 'description' => 'Elapsed time through popup open, dismiss, or submit events where supported.', 'helper' => 'seconds'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'flow',
                'label' => 'Flow',
                'description' => 'Top-level analytics domain for reconstructed path progression.',
                'summaryShort' => 'Flow summary will be generated from funnel progression and drop-off.',
                'summaryFull' => 'Flow will summarize supported funnel progression, completion, and leakage using the existing funnel analysis service and current flat page.',
                'subClusters' => [
                    [
                        'key' => 'funnels',
                        'label' => 'Funnels',
                        'description' => 'Sub-Cluster for supported session funnels and their progression steps.',
                        'summaryShort' => 'Funnels summary will be generated from supported step progression.',
                        'summaryFull' => 'Funnels will summarize supported step progression, completion, and drop-off from the current session-based funnel analysis output.',
                        'flatRouteName' => 'admin.analytics.funnels.index',
                        'metricGroups' => [
                            [
                                'key' => 'funnel_performance',
                                'label' => 'Funnel Performance',
                                'description' => 'Funnel completion, drop-off, and elapsed path duration.',
                                'metrics' => [
                                    ['key' => 'completion_rate', 'label' => 'Completion Rate', 'description' => 'Share of funnel entrants that reached the supported end state.'],
                                    ['key' => 'drop_off', 'label' => 'Drop-off', 'description' => 'Observed leakage between supported funnel steps.'],
                                    ['key' => 'duration', 'label' => 'Duration', 'description' => 'Average elapsed time across the supported funnel path.', 'helper' => 'seconds'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'behavior',
                'label' => 'Behavior',
                'description' => 'Top-level analytics domain for observed session patterns.',
                'summaryShort' => 'Behavior summary will be generated from scenario assignment patterns.',
                'summaryFull' => 'Behavior will summarize primary and supporting session patterns derived from current scenario assignment logic without altering the existing flat page.',
                'subClusters' => [
                    [
                        'key' => 'scenarios',
                        'label' => 'Scenarios',
                        'description' => 'Sub-Cluster for rule-based session scenarios and supporting patterns.',
                        'summaryShort' => 'Scenarios summary will be generated from scenario volume and conversion movement.',
                        'summaryFull' => 'Scenarios will summarize rule-based session patterns, their conversion movement, and representative sample journeys using the current flat report.',
                        'flatRouteName' => 'admin.analytics.scenarios.index',
                        'metricGroups' => [
                            [
                                'key' => 'scenario_performance',
                                'label' => 'Scenario Performance',
                                'description' => 'Scenario session volume, conversion rate, and event-based duration.',
                                'metrics' => [
                                    ['key' => 'views', 'label' => 'Sessions', 'description' => 'Observed sessions assigned to scenarios for the selected range.'],
                                    ['key' => 'conversion_rate', 'label' => 'Conversion Rate', 'description' => 'Share of assigned sessions that converted.'],
                                    ['key' => 'duration', 'label' => 'Duration', 'description' => 'Observed event-based session duration for scenarios.', 'helper' => 'seconds'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'results',
                'label' => 'Conversions',
                'description' => 'Top-level analytics domain for outcome counts and completion results.',
                'summaryShort' => 'Conversions summary will be generated from conversion totals and timing movement.',
                'summaryFull' => 'Conversions will summarize conversion totals, type mix, and timing from the current flat conversions page before any nested detail pages are added.',
                'subClusters' => [
                    [
                        'key' => 'conversions',
                        'label' => 'Conversions',
                        'description' => 'Sub-Cluster for conversion totals, type mix, and timing.',
                        'summaryShort' => 'Conversions summary will be generated from total conversions and conversion types.',
                        'summaryFull' => 'Conversions will summarize total movement, conversion type mix, and time-to-conversion signals from the current flat report.',
                        'flatRouteName' => 'admin.analytics.conversions.index',
                        'metricGroups' => [
                            [
                                'key' => 'conversion_performance',
                                'label' => 'Conversion Performance',
                                'description' => 'Conversion outcome volume and time-to-conversion.',
                                'metrics' => [
                                    ['key' => 'submissions', 'label' => 'Conversions', 'description' => 'Observed conversions recorded for the selected range.'],
                                    ['key' => 'time_to_conversion', 'label' => 'Time to Conversion', 'description' => 'Elapsed time from session start to first conversion where supported.', 'helper' => 'seconds'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'source',
                'label' => 'Source',
                'description' => 'Top-level analytics domain for attribution and source association.',
                'summaryShort' => 'Source summary will be generated from attribution coverage and source movement.',
                'summaryFull' => 'Source will summarize attribution coverage and top source associations using the current flat attribution page and existing attribution summaries.',
                'subClusters' => [
                    [
                        'key' => 'attribution',
                        'label' => 'Attribution',
                        'description' => 'Sub-Cluster for attributed and unattributed conversion source summaries.',
                        'summaryShort' => 'Attribution summary will be generated from attributed and unattributed conversion movement.',
                        'summaryFull' => 'Attribution will summarize how observed sources appear across attribution scopes while preserving the current flat attribution route.',
                        'flatRouteName' => 'admin.analytics.attribution.index',
                        'metricGroups' => [
                            [
                                'key' => 'attribution_performance',
                                'label' => 'Attribution Performance',
                                'description' => 'Attributed conversion volume and attribution coverage.',
                                'metrics' => [
                                    ['key' => 'submissions', 'label' => 'Attributed Conversions', 'description' => 'Observed conversions attributed for the selected range.'],
                                    ['key' => 'attribution_coverage', 'label' => 'Attribution Coverage', 'description' => 'Coverage of conversion attribution for the selected range.'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
