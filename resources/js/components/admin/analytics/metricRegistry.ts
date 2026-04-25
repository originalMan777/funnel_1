import {
    formatCompactNumber,
    formatDuration,
    formatNumber,
    formatPercent,
} from '@/components/admin/analytics/formatters';

export type MetricCategoryKey =
    | 'traffic'
    | 'capture'
    | 'flow'
    | 'behavior'
    | 'results'
    | 'source';

export type OverviewMetricKey =
    | 'conversions'
    | 'conversion_rate'
    | 'cta_clicks'
    | 'cta_conversion_rate'
    | 'lead_form_submissions'
    | 'funnel_completion_rate'
    | 'top_drop_off'
    | 'median_time_to_conversion'
    | 'median_session_duration'
    | 'top_scenario'
    | 'scenario_conversion_rate'
    | 'attribution_coverage';

export type OverviewSupportedMetricKey =
    | OverviewMetricKey
    | 'page_views'
    | 'popup_submissions'
    | 'average_funnel_elapsed'
    | 'attributed_conversions'
    | 'unattributed_conversions';

export type OverviewChartSlot =
    | 'trend'
    | 'compare'
    | 'funnel'
    | 'distribution'
    | 'timing'
    | 'mix'
    | 'share'
    | 'touches';

export type QuickViewVisual =
    | {
          kind: 'trend';
          rows: Array<Record<string, string | number>>;
          series: Array<{ key: string; label: string; colorClass?: string }>;
      }
    | {
          kind: 'comparison';
          rows: Array<{
              label: string;
              value: number;
              context?: string | null;
          }>;
      }
    | {
          kind: 'funnel';
          steps: Array<{
              key: string;
              label: string;
              count: number;
              dropOff?: number | null;
          }>;
      }
    | {
          kind: 'mini';
          items: Array<{ label: string; value: number }>;
      };

export type QuickViewPayload = {
    key: OverviewMetricKey;
    category: MetricCategoryKey;
    label: string;
    title: string;
    value: string;
    meta?: string | null;
    context: string;
    deltaLabel?: string | null;
    deltaValue?: string | null;
    supportingTitle?: string | null;
    breakdownTitle?: string | null;
    breakdown?: Array<{ label: string; value: string; note?: string | null }>;
    drilldownHref: string;
    drilldownLabel: string;
    visual?: QuickViewVisual | null;
};

export type MetricCategoryOption = {
    key: MetricCategoryKey | 'all';
    label: string;
};

export type OverviewMetricRegistrySource = {
    readiness: {
        session_inactivity_timeout_minutes: number;
    };
    overview: {
        summary_cards: {
            page_views: number;
            cta_clicks: number;
            lead_form_submissions: number;
            popup_submissions: number;
            conversions: number;
            average_session_duration_seconds: number | null;
            median_session_duration_seconds: number | null;
            average_time_to_conversion_seconds: number | null;
            median_time_to_conversion_seconds: number | null;
        };
        trend: Array<{
            date: string;
            page_views: number;
            cta_clicks: number;
            lead_form_submissions: number;
            popup_submissions: number;
            conversions: number;
        }>;
        conversion_types: Array<{
            conversion_type_id: number;
            label: string;
            total: number;
        }>;
        top_scenarios: Array<{
            scenario_key: string;
            label: string;
            description: string | null;
            sessions: number;
            converted_sessions: number;
            conversion_total: number;
            conversion_rate: number | null;
            average_events: number;
            average_session_duration_seconds: number | null;
            median_session_duration_seconds: number | null;
        }>;
        top_funnels: Array<{
            key: string;
            label: string;
            description: string;
            conversion_count: number;
            average_elapsed_seconds: number | null;
            top_drop_off?: {
                label: string;
                count: number;
                drop_off_to_next: number;
            } | null;
            steps: Array<{
                key: string;
                label: string;
                count: number;
                drop_off_to_next: number;
            }>;
        }>;
        attribution: {
            overview: {
                attributed_conversions: number;
                unattributed_conversions: number;
            };
        };
    };
    links: {
        pages: string;
        ctas: string;
        leadBoxes: string;
        popups: string;
        funnels: string;
        scenarios: string;
        conversions: string;
        attribution: string;
    };
};

export type OverviewMetricSummary<
    Key extends OverviewSupportedMetricKey = OverviewSupportedMetricKey,
> = {
    key: Key;
    category: MetricCategoryKey;
    label: string;
    value: string;
    description: string;
    drilldownHref: string;
    drilldownLabel: string;
};

export type OverviewHeroMetric = {
    key: OverviewMetricKey;
    label: string;
    value: string;
    hint: string;
    tone: 'emerald' | 'sky';
};

export type OverviewCategoryTile = {
    key: MetricCategoryKey;
    title: string;
    description: string;
    links: Array<{ label: string; href: string }>;
    metrics: Array<
        Pick<OverviewMetricSummary, 'key' | 'label' | 'value' | 'description'>
    >;
    chartSlots: OverviewChartSlot[];
    quickViewMetricKeys: OverviewMetricKey[];
};

export type OverviewAnalyticsMap = {
    metrics: Record<OverviewSupportedMetricKey, OverviewMetricSummary>;
    qbarMetrics: QuickViewPayload[];
    heroMetrics: OverviewHeroMetric[];
    categoryTiles: OverviewCategoryTile[];
};

type OverviewComputed = {
    leadSubmissionTotal: number;
    topScenario: OverviewMetricRegistrySource['overview']['top_scenarios'][number] | null;
    topFunnel: OverviewMetricRegistrySource['overview']['top_funnels'][number] | null;
    topDropOff: OverviewMetricRegistrySource['overview']['top_funnels'][number]['top_drop_off'] | null;
    conversionRate: number | null;
    ctaConversionRate: number | null;
    funnelCompletionRate: number | null;
    attributionCoverageRate: number | null;
    scenarioComparisonRows: Array<{
        label: string;
        value: number;
        context?: string | null;
    }>;
    attributionCoverageVisual: Array<{ label: string; value: number }>;
};

const categoryLabels: Record<MetricCategoryKey, string> = {
    traffic: 'Traffic',
    capture: 'Lead Capture',
    flow: 'Flow',
    behavior: 'Behavior',
    results: 'Conversions',
    source: 'Source',
};

const percent = (numerator: number, denominator: number) => {
    if (denominator <= 0) {
        return null;
    }

    return (numerator / denominator) * 100;
};

const buildOverviewComputed = (
    source: OverviewMetricRegistrySource,
): OverviewComputed => {
    const { overview } = source;
    const leadSubmissionTotal =
        overview.summary_cards.lead_form_submissions +
        overview.summary_cards.popup_submissions;
    const topScenario = overview.top_scenarios[0] ?? null;
    const topFunnel = overview.top_funnels[0] ?? null;
    const topDropOff = topFunnel?.top_drop_off ?? null;
    const conversionRate = percent(
        overview.summary_cards.conversions,
        overview.summary_cards.page_views,
    );
    const ctaConversionRate = percent(
        overview.summary_cards.conversions,
        overview.summary_cards.cta_clicks,
    );
    const funnelCompletionRate = topFunnel?.steps.length
        ? percent(topFunnel.conversion_count, topFunnel.steps[0].count)
        : null;
    const attributionCoverageRate = percent(
        overview.attribution.overview.attributed_conversions,
        overview.summary_cards.conversions,
    );

    return {
        leadSubmissionTotal,
        topScenario,
        topFunnel,
        topDropOff,
        conversionRate,
        ctaConversionRate,
        funnelCompletionRate,
        attributionCoverageRate,
        scenarioComparisonRows: overview.top_scenarios.slice(0, 5).map((row) => ({
            label: row.label,
            value: row.sessions,
            context: `${formatPercent(row.conversion_rate)} conversion rate`,
        })),
        attributionCoverageVisual: [
            {
                label: 'Attributed',
                value: overview.attribution.overview.attributed_conversions,
            },
            {
                label: 'Unattributed',
                value: overview.attribution.overview.unattributed_conversions,
            },
        ],
    };
};

const buildMetricSummaries = (
    source: OverviewMetricRegistrySource,
    computed: OverviewComputed,
): Record<OverviewSupportedMetricKey, OverviewMetricSummary> => {
    const { overview, links } = source;

    return {
        page_views: {
            key: 'page_views',
            category: 'traffic',
            label: 'Page Views',
            value: formatNumber(overview.summary_cards.page_views),
            description: 'Tracked page activity across the selected range.',
            drilldownHref: links.pages,
            drilldownLabel: 'Open page report',
        },
        cta_clicks: {
            key: 'cta_clicks',
            category: 'traffic',
            label: 'CTA Clicks',
            value: formatNumber(overview.summary_cards.cta_clicks),
            description: 'Tracked CTA interactions across the selected range.',
            drilldownHref: links.ctas,
            drilldownLabel: 'Open CTA report',
        },
        cta_conversion_rate: {
            key: 'cta_conversion_rate',
            category: 'traffic',
            label: 'CTA Conv. Rate',
            value: formatPercent(computed.ctaConversionRate),
            description:
                'Conversions divided by CTA clicks in the same date window.',
            drilldownHref: links.ctas,
            drilldownLabel: 'Open CTA report',
        },
        lead_form_submissions: {
            key: 'lead_form_submissions',
            category: 'capture',
            label: 'Lead Form Submissions',
            value: formatNumber(overview.summary_cards.lead_form_submissions),
            description:
                'Lead-box form submissions captured in the selected range.',
            drilldownHref: links.leadBoxes,
            drilldownLabel: 'Open lead-box report',
        },
        popup_submissions: {
            key: 'popup_submissions',
            category: 'capture',
            label: 'Popup Submissions',
            value: formatNumber(overview.summary_cards.popup_submissions),
            description:
                'Popup form submissions captured in the selected range.',
            drilldownHref: links.popups,
            drilldownLabel: 'Open popup report',
        },
        funnel_completion_rate: {
            key: 'funnel_completion_rate',
            category: 'flow',
            label: 'Funnel Completion Rate',
            value: formatPercent(computed.funnelCompletionRate),
            description:
                'Reference funnel conversions divided by first-step entrants.',
            drilldownHref: links.funnels,
            drilldownLabel: 'Open funnel report',
        },
        top_drop_off: {
            key: 'top_drop_off',
            category: 'flow',
            label: 'Top Drop-off',
            value: computed.topDropOff?.label ?? '—',
            description: 'The sharpest leak in the current reference funnel.',
            drilldownHref: links.funnels,
            drilldownLabel: 'Open funnel report',
        },
        average_funnel_elapsed: {
            key: 'average_funnel_elapsed',
            category: 'flow',
            label: 'Average Funnel Elapsed',
            value: formatDuration(computed.topFunnel?.average_elapsed_seconds),
            description:
                'Average elapsed time across the current reference funnel.',
            drilldownHref: links.funnels,
            drilldownLabel: 'Open funnel report',
        },
        top_scenario: {
            key: 'top_scenario',
            category: 'behavior',
            label: 'Top Scenario',
            value: computed.topScenario?.label ?? '—',
            description:
                'Highest-volume scenario in the selected date range.',
            drilldownHref: links.scenarios,
            drilldownLabel: 'Open scenario report',
        },
        scenario_conversion_rate: {
            key: 'scenario_conversion_rate',
            category: 'behavior',
            label: 'Scenario Conv. Rate',
            value: formatPercent(computed.topScenario?.conversion_rate),
            description:
                'Conversion rate for the current top-volume scenario.',
            drilldownHref: links.scenarios,
            drilldownLabel: 'Open scenario report',
        },
        median_session_duration: {
            key: 'median_session_duration',
            category: 'behavior',
            label: 'Median Session Duration',
            value: formatDuration(
                overview.summary_cards.median_session_duration_seconds,
            ),
            description:
                'Median session duration across the selected range.',
            drilldownHref: links.pages,
            drilldownLabel: 'Open page report',
        },
        conversions: {
            key: 'conversions',
            category: 'results',
            label: 'Conversions',
            value: formatNumber(overview.summary_cards.conversions),
            description: 'Completed conversions in the selected range.',
            drilldownHref: links.conversions,
            drilldownLabel: 'Open conversion report',
        },
        conversion_rate: {
            key: 'conversion_rate',
            category: 'results',
            label: 'Conversion Rate',
            value: formatPercent(computed.conversionRate),
            description:
                'Conversions divided by tracked page views in the same range.',
            drilldownHref: links.pages,
            drilldownLabel: 'Open page report',
        },
        median_time_to_conversion: {
            key: 'median_time_to_conversion',
            category: 'results',
            label: 'Median Time to Conversion',
            value: formatDuration(
                overview.summary_cards.median_time_to_conversion_seconds,
            ),
            description:
                'Typical elapsed time to conversion in the selected range.',
            drilldownHref: links.conversions,
            drilldownLabel: 'Open conversion report',
        },
        attributed_conversions: {
            key: 'attributed_conversions',
            category: 'source',
            label: 'Attributed Conversions',
            value: formatNumber(
                overview.attribution.overview.attributed_conversions,
            ),
            description: 'Conversions that have attribution coverage.',
            drilldownHref: links.attribution,
            drilldownLabel: 'Open attribution report',
        },
        unattributed_conversions: {
            key: 'unattributed_conversions',
            category: 'source',
            label: 'Unattributed Conversions',
            value: formatNumber(
                overview.attribution.overview.unattributed_conversions,
            ),
            description: 'Conversions that are still unattributed.',
            drilldownHref: links.attribution,
            drilldownLabel: 'Open attribution report',
        },
        attribution_coverage: {
            key: 'attribution_coverage',
            category: 'source',
            label: 'Attribution Coverage',
            value: formatPercent(computed.attributionCoverageRate),
            description:
                'Attributed conversions divided by total conversions.',
            drilldownHref: links.attribution,
            drilldownLabel: 'Open attribution report',
        },
    };
};

function buildQuickViewMetric(
    metric: OverviewMetricSummary<OverviewMetricKey>,
    payload: Omit<
        QuickViewPayload,
        | 'key'
        | 'category'
        | 'label'
        | 'title'
        | 'value'
        | 'drilldownHref'
        | 'drilldownLabel'
    > & {
        title?: string;
    },
): QuickViewPayload {
    return {
        key: metric.key,
        category: metric.category,
        label: metric.label,
        title: payload.title ?? metric.label,
        value: metric.value,
        drilldownHref: metric.drilldownHref,
        drilldownLabel: metric.drilldownLabel,
        ...payload,
    };
}

const pickTileMetrics = (
    metrics: Record<OverviewSupportedMetricKey, OverviewMetricSummary>,
    keys: OverviewSupportedMetricKey[],
) =>
    keys.map((key) => {
        const metric = metrics[key];

        return {
            key: metric.key,
            label: metric.label,
            value: metric.value,
            description: metric.description,
        };
    });

const buildCategoryTiles = (
    source: OverviewMetricRegistrySource,
    metrics: Record<OverviewSupportedMetricKey, OverviewMetricSummary>,
): OverviewCategoryTile[] => {
    const { links } = source;

    return [
        {
            key: 'traffic',
            title: 'Traffic',
            description:
                'Top-of-system movement across pages and CTA interactions.',
            links: [
                { label: 'Pages', href: links.pages },
                { label: 'CTAs', href: links.ctas },
            ],
            metrics: pickTileMetrics(metrics, ['page_views', 'cta_clicks']),
            chartSlots: ['trend', 'compare'],
            quickViewMetricKeys: ['cta_clicks', 'cta_conversion_rate'],
        },
        {
            key: 'capture',
            title: 'Lead Capture',
            description: 'Lead capture behavior across lead boxes and popups.',
            links: [
                { label: 'Lead Boxes', href: links.leadBoxes },
                { label: 'Popups', href: links.popups },
            ],
            metrics: pickTileMetrics(metrics, [
                'lead_form_submissions',
                'popup_submissions',
            ]),
            chartSlots: ['trend', 'mix'],
            quickViewMetricKeys: ['lead_form_submissions'],
        },
        {
            key: 'flow',
            title: 'Flow',
            description:
                'Step completion, leaks, and elapsed movement through funnels.',
            links: [{ label: 'Funnels', href: links.funnels }],
            metrics: pickTileMetrics(metrics, [
                'funnel_completion_rate',
                'top_drop_off',
            ]),
            chartSlots: ['funnel', 'timing'],
            quickViewMetricKeys: [
                'funnel_completion_rate',
                'top_drop_off',
            ],
        },
        {
            key: 'behavior',
            title: 'Behavior',
            description:
                'Scenario patterns and how sessions behave before converting.',
            links: [{ label: 'Scenarios', href: links.scenarios }],
            metrics: pickTileMetrics(metrics, [
                'top_scenario',
                'scenario_conversion_rate',
            ]),
            chartSlots: ['distribution', 'timing'],
            quickViewMetricKeys: [
                'top_scenario',
                'scenario_conversion_rate',
                'median_session_duration',
            ],
        },
        {
            key: 'results',
            title: 'Conversions',
            description:
                'Outcome totals, efficiency, and elapsed conversion timing.',
            links: [{ label: 'Conversions', href: links.conversions }],
            metrics: pickTileMetrics(metrics, [
                'conversions',
                'conversion_rate',
            ]),
            chartSlots: ['trend', 'mix'],
            quickViewMetricKeys: [
                'conversions',
                'conversion_rate',
                'median_time_to_conversion',
            ],
        },
        {
            key: 'source',
            title: 'Source',
            description:
                'Attribution coverage and source-level conversion visibility.',
            links: [{ label: 'Attribution', href: links.attribution }],
            metrics: pickTileMetrics(metrics, [
                'attributed_conversions',
                'unattributed_conversions',
            ]),
            chartSlots: ['share', 'touches'],
            quickViewMetricKeys: ['attribution_coverage'],
        },
    ];
};

export function buildOverviewMetricGroups(
    metrics: QuickViewPayload[],
): MetricCategoryOption[] {
    const presentCategories = new Set(metrics.map((metric) => metric.category));

    return [
        { key: 'all', label: 'All Metrics' },
        ...Object.entries(categoryLabels)
            .filter(([key]) => presentCategories.has(key as MetricCategoryKey))
            .map(([key, label]) => ({
                key: key as MetricCategoryKey,
                label,
            })),
    ];
}

export function buildOverviewAnalyticsMap(
    source: OverviewMetricRegistrySource,
): OverviewAnalyticsMap {
    const { readiness, overview } = source;
    const computed = buildOverviewComputed(source);
    const metrics = buildMetricSummaries(source, computed);

    const qbarMetrics: QuickViewPayload[] = [
        buildQuickViewMetric(metrics.conversions, {
            meta: `${formatNumber(computed.leadSubmissionTotal)} tracked submissions`,
            deltaLabel: 'Submission events',
            deltaValue: formatNumber(computed.leadSubmissionTotal),
            context:
                'This keeps the command center anchored on completed conversion outcomes first, with supporting submission context nearby instead of dominating the page.',
            supportingTitle: 'Daily conversion movement',
            breakdownTitle: 'Conversion context',
            breakdown: overview.conversion_types.slice(0, 4).map((row) => ({
                label: row.label,
                value: formatNumber(row.total),
                note: 'Conversion type total',
            })),
            visual: {
                kind: 'trend',
                rows: overview.trend,
                series: [
                    {
                        key: 'conversions',
                        label: 'Conversions',
                        colorClass: 'bg-slate-900',
                    },
                    {
                        key: 'lead_form_submissions',
                        label: 'Lead submissions',
                        colorClass: 'bg-emerald-500',
                    },
                ],
            },
        }),
        buildQuickViewMetric(metrics.conversion_rate, {
            meta: `${metrics.page_views.value} page views in range`,
            deltaLabel: 'Coverage',
            deltaValue: metrics.attribution_coverage.value,
            context:
                'The overview uses a single conversion-rate lens so volume and efficiency stay connected without repeating the full pages, CTA, and attribution reports on one screen.',
            supportingTitle: 'Views vs conversions',
            breakdownTitle: 'Rate context',
            breakdown: [
                {
                    label: 'Tracked page views',
                    value: metrics.page_views.value,
                },
                {
                    label: 'CTA click-to-conversion',
                    value: metrics.cta_conversion_rate.value,
                },
                {
                    label: 'Attributed coverage',
                    value: metrics.attribution_coverage.value,
                    note: 'Attributed conversions divided by total conversions',
                },
            ],
            visual: {
                kind: 'trend',
                rows: overview.trend,
                series: [
                    {
                        key: 'page_views',
                        label: 'Page views',
                        colorClass: 'bg-slate-300',
                    },
                    {
                        key: 'conversions',
                        label: 'Conversions',
                        colorClass: 'bg-slate-900',
                    },
                ],
            },
        }),
        buildQuickViewMetric(metrics.cta_clicks, {
            meta: `${metrics.cta_conversion_rate.value} click-to-conversion`,
            deltaLabel: 'Downstream conversion',
            deltaValue: metrics.cta_conversion_rate.value,
            context:
                'CTA activity gives the fastest read on audience response before the final conversion step, so the quick view pairs click volume with downstream conversion context.',
            supportingTitle: 'Clicks vs conversions',
            breakdownTitle: 'CTA context',
            breakdown: [
                {
                    label: 'Lead form submissions',
                    value: metrics.lead_form_submissions.value,
                },
                {
                    label: 'Popup submissions',
                    value: metrics.popup_submissions.value,
                },
                {
                    label: 'Conversion rate from clicks',
                    value: metrics.cta_conversion_rate.value,
                },
            ],
            visual: {
                kind: 'trend',
                rows: overview.trend,
                series: [
                    {
                        key: 'cta_clicks',
                        label: 'CTA clicks',
                        colorClass: 'bg-slate-900',
                    },
                    {
                        key: 'conversions',
                        label: 'Conversions',
                        colorClass: 'bg-amber-500',
                    },
                ],
            },
        }),
        buildQuickViewMetric(metrics.cta_conversion_rate, {
            title: 'CTA Conversion Rate',
            meta: `${formatCompactNumber(overview.summary_cards.cta_clicks)} CTA clicks`,
            deltaLabel: 'Conversions',
            deltaValue: metrics.conversions.value,
            context:
                'This is the cleanest click-to-conversion read currently available from the Overview payload, tying CTA response directly to recorded conversions.',
            supportingTitle: 'CTA efficiency context',
            breakdownTitle: 'Efficiency context',
            breakdown: [
                {
                    label: 'CTA clicks',
                    value: metrics.cta_clicks.value,
                },
                {
                    label: 'Conversions',
                    value: metrics.conversions.value,
                },
                {
                    label: 'Lead submissions',
                    value: formatNumber(computed.leadSubmissionTotal),
                },
            ],
            visual: {
                kind: 'comparison',
                rows: [
                    {
                        label: 'CTA clicks',
                        value: overview.summary_cards.cta_clicks,
                        context: metrics.cta_clicks.value,
                    },
                    {
                        label: 'Conversions',
                        value: overview.summary_cards.conversions,
                        context: metrics.conversions.value,
                    },
                    {
                        label: 'Lead submissions',
                        value: computed.leadSubmissionTotal,
                        context: formatNumber(computed.leadSubmissionTotal),
                    },
                ],
            },
        }),
        buildQuickViewMetric(metrics.lead_form_submissions, {
            meta: `${metrics.popup_submissions.value} popup submissions alongside forms`,
            deltaLabel: 'Popup-assisted',
            deltaValue: metrics.popup_submissions.value,
            context:
                'Lead capture stays focused on completed submissions here. The breakdown keeps popup-assisted capture visible without letting the overview turn into a surface-by-surface table.',
            supportingTitle: 'Submission mix',
            breakdownTitle: 'Lead capture context',
            breakdown: [
                {
                    label: 'Lead form submissions',
                    value: metrics.lead_form_submissions.value,
                },
                {
                    label: 'Popup submissions',
                    value: metrics.popup_submissions.value,
                },
                {
                    label: 'Combined submission events',
                    value: formatNumber(computed.leadSubmissionTotal),
                },
            ],
            visual: {
                kind: 'comparison',
                rows: [
                    {
                        label: 'Lead forms',
                        value: overview.summary_cards.lead_form_submissions,
                    },
                    {
                        label: 'Popups',
                        value: overview.summary_cards.popup_submissions,
                    },
                    {
                        label: 'Conversions',
                        value: overview.summary_cards.conversions,
                    },
                ],
            },
        }),
        buildQuickViewMetric(metrics.median_time_to_conversion, {
            meta: `Average ${formatDuration(overview.summary_cards.average_time_to_conversion_seconds)}`,
            deltaLabel: 'Average',
            deltaValue: formatDuration(
                overview.summary_cards.average_time_to_conversion_seconds,
            ),
            context:
                'Median elapsed time keeps the overview honest about the typical path length without flattening everything into averages or forcing the full conversions report into this page.',
            supportingTitle: 'Elapsed-time context',
            breakdownTitle: 'Time context',
            breakdown: [
                {
                    label: 'Average time to conversion',
                    value: formatDuration(
                        overview.summary_cards.average_time_to_conversion_seconds,
                    ),
                },
                {
                    label: 'Median session duration',
                    value: metrics.median_session_duration.value,
                },
                {
                    label: 'Session inactivity timeout',
                    value: `${readiness.session_inactivity_timeout_minutes} minutes`,
                },
            ],
            visual: {
                kind: 'comparison',
                rows: [
                    {
                        label: 'Median time to conversion',
                        value: Math.round(
                            overview.summary_cards
                                .median_time_to_conversion_seconds ?? 0,
                        ),
                        context: metrics.median_time_to_conversion.value,
                    },
                    {
                        label: 'Average time to conversion',
                        value: Math.round(
                            overview.summary_cards
                                .average_time_to_conversion_seconds ?? 0,
                        ),
                        context: formatDuration(
                            overview.summary_cards
                                .average_time_to_conversion_seconds,
                        ),
                    },
                    {
                        label: 'Median session duration',
                        value: Math.round(
                            overview.summary_cards
                                .median_session_duration_seconds ?? 0,
                        ),
                        context: metrics.median_session_duration.value,
                    },
                ],
            },
        }),
        buildQuickViewMetric(metrics.median_session_duration, {
            meta: `Average ${formatDuration(overview.summary_cards.average_session_duration_seconds)}`,
            deltaLabel: 'Average',
            deltaValue: formatDuration(
                overview.summary_cards.average_session_duration_seconds,
            ),
            context:
                'Session duration stays as a supporting behavioral read, useful for contextualizing the shape of journeys without competing with the conversion-focused headline metrics.',
            supportingTitle: 'Session length context',
            breakdownTitle: 'Duration context',
            breakdown: [
                {
                    label: 'Average session duration',
                    value: formatDuration(
                        overview.summary_cards.average_session_duration_seconds,
                    ),
                },
                {
                    label: 'Median time to conversion',
                    value: metrics.median_time_to_conversion.value,
                },
                {
                    label: 'CTA clicks',
                    value: metrics.cta_clicks.value,
                },
            ],
            visual: {
                kind: 'comparison',
                rows: [
                    {
                        label: 'Median session duration',
                        value: Math.round(
                            overview.summary_cards
                                .median_session_duration_seconds ?? 0,
                        ),
                        context: metrics.median_session_duration.value,
                    },
                    {
                        label: 'Average session duration',
                        value: Math.round(
                            overview.summary_cards
                                .average_session_duration_seconds ?? 0,
                        ),
                        context: formatDuration(
                            overview.summary_cards
                                .average_session_duration_seconds,
                        ),
                    },
                    {
                        label: 'Median time to conversion',
                        value: Math.round(
                            overview.summary_cards
                                .median_time_to_conversion_seconds ?? 0,
                        ),
                        context: metrics.median_time_to_conversion.value,
                    },
                ],
            },
        }),
        buildQuickViewMetric(metrics.attribution_coverage, {
            meta: `${metrics.attributed_conversions.value} attributed conversions`,
            deltaLabel: 'Attributed conversions',
            deltaValue: metrics.attributed_conversions.value,
            context:
                'Overview keeps attribution framed as coverage and directional source context. The full attribution report still owns the detailed touch-level breakdowns.',
            supportingTitle: 'Coverage split',
            breakdownTitle: 'Attribution context',
            breakdown: [
                {
                    label: 'Attributed conversions',
                    value: metrics.attributed_conversions.value,
                },
                {
                    label: 'Unattributed conversions',
                    value: metrics.unattributed_conversions.value,
                },
                {
                    label: 'Total conversions',
                    value: metrics.conversions.value,
                },
            ],
            visual: {
                kind: 'mini',
                items: computed.attributionCoverageVisual,
            },
        }),
    ];

    if (computed.topFunnel && computed.funnelCompletionRate !== null) {
        qbarMetrics.push(
            buildQuickViewMetric(metrics.funnel_completion_rate, {
                meta: computed.topFunnel.label,
                deltaLabel: 'Reference funnel',
                deltaValue: computed.topFunnel.label,
                context:
                    'The overview uses one reference funnel so you can spot completion health quickly, while the dedicated funnel report retains the full step-by-step analysis.',
                supportingTitle: computed.topFunnel.label,
                breakdownTitle: 'Funnel context',
                breakdown: [
                    {
                        label: 'Conversions',
                        value: formatNumber(
                            computed.topFunnel.conversion_count,
                        ),
                    },
                    {
                        label: 'Top drop-off',
                        value: metrics.top_drop_off.value,
                        note: computed.topDropOff
                            ? `${formatNumber(computed.topDropOff.drop_off_to_next)} sessions lost before the next step`
                            : 'No measurable top drop-off',
                    },
                    {
                        label: 'Average elapsed',
                        value: metrics.average_funnel_elapsed.value,
                    },
                ],
                visual: {
                    kind: 'funnel',
                    steps: computed.topFunnel.steps.map((step) => ({
                        key: step.key,
                        label: step.label,
                        count: step.count,
                        dropOff: step.drop_off_to_next,
                    })),
                },
            }),
        );
    }

    if (computed.topDropOff && computed.topFunnel) {
        qbarMetrics.push(
            buildQuickViewMetric(metrics.top_drop_off, {
                meta: `${formatCompactNumber(computed.topDropOff.drop_off_to_next)} sessions lost`,
                deltaLabel: 'Reference funnel',
                deltaValue: computed.topFunnel.label,
                context:
                    'This metric isolates the sharpest leak in the reference funnel so the overview can surface the biggest journey problem without duplicating the full funnel report.',
                supportingTitle: 'Funnel step losses',
                breakdownTitle: 'Drop-off context',
                breakdown: [
                    {
                        label: 'Sessions lost',
                        value: formatNumber(
                            computed.topDropOff.drop_off_to_next,
                        ),
                    },
                    {
                        label: 'Reached step',
                        value: formatNumber(computed.topDropOff.count),
                    },
                    {
                        label: 'Average elapsed',
                        value: metrics.average_funnel_elapsed.value,
                    },
                ],
                visual: {
                    kind: 'funnel',
                    steps: computed.topFunnel.steps.map((step) => ({
                        key: step.key,
                        label: step.label,
                        count: step.count,
                        dropOff: step.drop_off_to_next,
                    })),
                },
            }),
        );
    }

    if (computed.topScenario) {
        qbarMetrics.push(
            buildQuickViewMetric(metrics.top_scenario, {
                meta: `${metrics.scenario_conversion_rate.value} conversion rate`,
                deltaLabel: 'Sessions',
                deltaValue: formatNumber(computed.topScenario.sessions),
                context:
                    'The overview uses the leading scenario as a fast read on which session pattern is carrying the most volume in the selected range.',
                supportingTitle: 'Scenario volume comparison',
                breakdownTitle: 'Scenario context',
                breakdown: [
                    {
                        label: 'Sessions',
                        value: formatNumber(computed.topScenario.sessions),
                    },
                    {
                        label: 'Conversions',
                        value: formatNumber(
                            computed.topScenario.conversion_total,
                        ),
                    },
                    {
                        label: 'Median session duration',
                        value: formatDuration(
                            computed.topScenario
                                .median_session_duration_seconds,
                        ),
                    },
                ],
                visual: {
                    kind: 'comparison',
                    rows: computed.scenarioComparisonRows,
                },
            }),
            buildQuickViewMetric(metrics.scenario_conversion_rate, {
                title: 'Scenario Conversion Rate',
                meta: computed.topScenario.label,
                deltaLabel: 'Scenario conversions',
                deltaValue: formatNumber(
                    computed.topScenario.conversion_total,
                ),
                context:
                    'This focuses the scenario lane on efficiency rather than volume, using the current top scenario already present in the Overview payload.',
                supportingTitle: 'Scenario volume comparison',
                breakdownTitle: 'Scenario rate context',
                breakdown: [
                    {
                        label: 'Sessions',
                        value: formatNumber(computed.topScenario.sessions),
                    },
                    {
                        label: 'Converted sessions',
                        value: formatNumber(
                            computed.topScenario.converted_sessions,
                        ),
                    },
                    {
                        label: 'Conversion total',
                        value: formatNumber(
                            computed.topScenario.conversion_total,
                        ),
                    },
                ],
                visual: {
                    kind: 'comparison',
                    rows: computed.scenarioComparisonRows,
                },
            }),
        );
    }

    return {
        metrics,
        qbarMetrics,
        heroMetrics: [
            {
                key: 'conversions',
                label: metrics.conversions.label,
                value: metrics.conversions.value,
                hint: 'The clearest top-line performance read for the selected range.',
                tone: 'emerald',
            },
            {
                key: 'conversion_rate',
                label: metrics.conversion_rate.label,
                value: metrics.conversion_rate.value,
                hint: 'Total conversions divided by tracked page views in the same date window.',
                tone: 'sky',
            },
        ],
        categoryTiles: buildCategoryTiles(source, metrics),
    };
}

export function buildOverviewMetricRegistry(
    source: OverviewMetricRegistrySource,
): QuickViewPayload[] {
    return buildOverviewAnalyticsMap(source).qbarMetrics;
}
