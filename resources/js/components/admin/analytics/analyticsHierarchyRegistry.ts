export type AnalyticsClusterKey =
    | 'traffic'
    | 'capture'
    | 'flow'
    | 'behavior'
    | 'results'
    | 'source';

export type AnalyticsSubClusterKey =
    | 'pages'
    | 'ctas'
    | 'lead_boxes'
    | 'popups'
    | 'funnels'
    | 'scenarios'
    | 'conversions'
    | 'attribution';

export type AnalyticsMetricKey =
    | 'views'
    | 'clicks'
    | 'ctr'
    | 'conversion_rate'
    | 'submissions'
    | 'dismissals'
    | 'failures'
    | 'open_rate'
    | 'completion_rate'
    | 'drop_off'
    | 'duration'
    | 'time_to_conversion'
    | 'attribution_coverage';

export type AnalyticsFilters = {
    from?: string;
    to?: string;
};

export type AnalyticsMetricDefinition = {
    key: AnalyticsMetricKey;
    label: string;
    description: string;
};

export type AnalyticsMetricGroupDefinition = {
    key: string;
    label: string;
    description: string;
    entityLabel: string;
    href: string | null;
    metrics: AnalyticsMetricDefinition[];
};

export type AnalyticsSubClusterDefinition = {
    key: AnalyticsSubClusterKey;
    clusterKey: AnalyticsClusterKey;
    label: string;
    description: string;
    summaryShort: string;
    summaryFull: string;
    flatRouteName: string;
    flatHref: string;
    href: string;
    metricGroups: AnalyticsMetricGroupDefinition[];
};

export type AnalyticsClusterDefinition = {
    key: AnalyticsClusterKey;
    label: string;
    description: string;
    summaryShort: string;
    summaryFull: string;
    href: string | null;
    subClusters: AnalyticsSubClusterDefinition[];
};

type AnalyticsSubClusterSeed = {
    key: AnalyticsSubClusterKey;
    label: string;
    description: string;
    summaryShort: string;
    summaryFull: string;
    flatRouteName: string;
    metricGroups: AnalyticsMetricGroupSeed[];
};

type AnalyticsMetricGroupSeed = {
    key: string;
    label: string;
    description: string;
    entityLabel: string;
    metrics: AnalyticsMetricDefinition[];
};

type AnalyticsClusterSeed = {
    key: AnalyticsClusterKey;
    label: string;
    description: string;
    summaryShort: string;
    summaryFull: string;
    subClusters: AnalyticsSubClusterSeed[];
};

const buildRouteParams = (filters?: AnalyticsFilters) => {
    const params: Record<string, string> = {};

    if (filters?.from) {
        params.from = filters.from;
    }

    if (filters?.to) {
        params.to = filters.to;
    }

    return params;
};

const buildAnalyticsFlatHref = (
    routeName: string,
    filters?: AnalyticsFilters,
): string => route(routeName, buildRouteParams(filters));

const buildAnalyticsSubClusterHref = (
    clusterKey: AnalyticsClusterKey,
    subClusterKey: AnalyticsSubClusterKey,
    filters?: AnalyticsFilters,
): string =>
    route('admin.analytics.sub-clusters.show', {
        clusterKey,
        subClusterKey,
        ...buildRouteParams(filters),
    });

const analyticsHierarchySeeds: AnalyticsClusterSeed[] = [
    {
        key: 'traffic',
        label: 'Traffic',
        description: 'Top-level analytics domain for audience movement and response.',
        summaryShort:
            'Traffic summary will be generated from page and CTA movement.',
        summaryFull:
            'Traffic will summarize movement across Pages and CTAs using the existing flat analytics reports before any nested navigation is introduced.',
        subClusters: [
            {
                key: 'pages',
                label: 'Pages',
                description:
                    'Sub-Cluster for tracked page performance and page-level movement.',
                summaryShort:
                    'Pages summary will be generated from tracked page views and conversions.',
                summaryFull:
                    'Pages will anchor the Traffic cluster with tracked page performance, conversion totals, and timing signals from the current flat analytics report.',
                flatRouteName: 'admin.analytics.pages.index',
                metricGroups: [
                    {
                        key: 'page_performance',
                        label: 'Page Performance',
                        description:
                            'Page-level reach, conversion quality, and conversion timing.',
                        entityLabel: 'Page',
                        metrics: [
                            {
                                key: 'views',
                                label: 'Views',
                                description:
                                    'Observed page views attributed to the metric group.',
                            },
                            {
                                key: 'conversion_rate',
                                label: 'Conversion Rate',
                                description:
                                    'Share of page views that later resulted in a conversion.',
                            },
                            {
                                key: 'time_to_conversion',
                                label: 'Time to Conversion',
                                description:
                                    'Elapsed time from page interaction to conversion where supported.',
                            },
                        ],
                    },
                ],
            },
            {
                key: 'ctas',
                label: 'CTAs',
                description:
                    'Sub-Cluster for tracked CTA visibility, clicks, and follow-through.',
                summaryShort:
                    'CTAs summary will be generated from impressions, clicks, and conversion movement.',
                summaryFull:
                    'CTAs will summarize how tracked calls to action are seen, clicked, and carried forward into later conversions using the existing flat report.',
                flatRouteName: 'admin.analytics.ctas.index',
                metricGroups: [
                    {
                        key: 'cta_performance',
                        label: 'CTA Performance',
                        description:
                            'CTA exposure, engagement, click-through, conversion quality, and timing.',
                        entityLabel: 'CTA',
                        metrics: [
                            {
                                key: 'views',
                                label: 'Impressions',
                                description:
                                    'Observed CTA impressions for the metric group.',
                            },
                            {
                                key: 'clicks',
                                label: 'Clicks',
                                description:
                                    'Observed CTA clicks for the metric group.',
                            },
                            {
                                key: 'ctr',
                                label: 'CTR',
                                description:
                                    'Click-through rate for the metric group.',
                            },
                            {
                                key: 'conversion_rate',
                                label: 'Conversion Rate',
                                description:
                                    'Share of CTA clicks that progressed into conversions.',
                            },
                            {
                                key: 'time_to_conversion',
                                label: 'Time to Conversion',
                                description:
                                    'Elapsed time from CTA interaction to later conversion where supported.',
                            },
                        ],
                    },
                ],
            },
        ],
    },
    {
        key: 'capture',
        label: 'Lead Capture',
        description: 'Top-level analytics domain for lead capture surfaces.',
        summaryShort:
            'Lead Capture summary will be generated from lead box and popup movement.',
        summaryFull:
            'Lead Capture will summarize how Lead Boxes and Popups expose, engage, and submit without changing the current flat analytics routes.',
        subClusters: [
            {
                key: 'lead_boxes',
                label: 'Lead Boxes',
                description:
                    'Sub-Cluster for tracked lead box exposure, engagement, and submission outcomes.',
                summaryShort:
                    'Lead Boxes summary will be generated from impressions, clicks, and submissions.',
                summaryFull:
                    'Lead Boxes will summarize lead capture surface performance using the current flat report for exposure, submission, and failure movement.',
                flatRouteName: 'admin.analytics.lead-boxes.index',
                metricGroups: [
                    {
                        key: 'lead_box_lifecycle',
                        label: 'Lead Box Lifecycle',
                        description:
                            'Lead box exposure, engagement, submissions, failures, and path duration.',
                        entityLabel: 'Lead Box',
                        metrics: [
                            {
                                key: 'views',
                                label: 'Impressions',
                                description:
                                    'Observed lead box impressions for the metric group.',
                            },
                            {
                                key: 'clicks',
                                label: 'Clicks',
                                description:
                                    'Observed lead box clicks for the metric group.',
                            },
                            {
                                key: 'submissions',
                                label: 'Submissions',
                                description:
                                    'Observed lead box submissions for the metric group.',
                            },
                            {
                                key: 'failures',
                                label: 'Failures',
                                description:
                                    'Observed lead form failures associated with the metric group.',
                            },
                            {
                                key: 'duration',
                                label: 'Duration',
                                description:
                                    'Elapsed time through the lead box submission path where supported.',
                            },
                        ],
                    },
                ],
            },
            {
                key: 'popups',
                label: 'Popups',
                description:
                    'Sub-Cluster for tracked popup eligibility, opens, dismissals, and submissions.',
                summaryShort:
                    'Popups summary will be generated from popup lifecycle movement.',
                summaryFull:
                    'Popups will summarize the popup lifecycle from eligibility through opens, dismissals, and submissions using the current flat report.',
                flatRouteName: 'admin.analytics.popups.index',
                metricGroups: [
                    {
                        key: 'popup_lifecycle',
                        label: 'Popup Lifecycle',
                        description:
                            'Popup impressions, open rate, dismissals, submissions, and lifecycle duration.',
                        entityLabel: 'Popup',
                        metrics: [
                            {
                                key: 'views',
                                label: 'Impressions',
                                description:
                                    'Observed popup impressions for the metric group.',
                            },
                            {
                                key: 'open_rate',
                                label: 'Open Rate',
                                description:
                                    'Share of popup impressions that progressed into opens.',
                            },
                            {
                                key: 'dismissals',
                                label: 'Dismissals',
                                description:
                                    'Observed popup dismissals for the metric group.',
                            },
                            {
                                key: 'submissions',
                                label: 'Submissions',
                                description:
                                    'Observed popup submissions for the metric group.',
                            },
                            {
                                key: 'duration',
                                label: 'Duration',
                                description:
                                    'Elapsed time through popup open, dismiss, or submit events where supported.',
                            },
                        ],
                    },
                ],
            },
        ],
    },
    {
        key: 'flow',
        label: 'Flow',
        description: 'Top-level analytics domain for reconstructed path progression.',
        summaryShort:
            'Flow summary will be generated from funnel progression and drop-off.',
        summaryFull:
            'Flow will summarize supported funnel progression, completion, and leakage using the existing funnel analysis service and current flat page.',
        subClusters: [
            {
                key: 'funnels',
                label: 'Funnels',
                description:
                    'Sub-Cluster for supported session funnels and their progression steps.',
                summaryShort:
                    'Funnels summary will be generated from supported step progression.',
                summaryFull:
                    'Funnels will summarize supported step progression, completion, and drop-off from the current session-based funnel analysis output.',
                flatRouteName: 'admin.analytics.funnels.index',
                metricGroups: [
                    {
                        key: 'funnel_performance',
                        label: 'Funnel Performance',
                        description:
                            'Funnel completion, drop-off, and elapsed path duration.',
                        entityLabel: 'Funnel',
                        metrics: [
                            {
                                key: 'completion_rate',
                                label: 'Completion Rate',
                                description:
                                    'Share of funnel entrants that reached the supported end state.',
                            },
                            {
                                key: 'drop_off',
                                label: 'Drop-off',
                                description:
                                    'Observed leakage between supported funnel steps.',
                            },
                            {
                                key: 'duration',
                                label: 'Duration',
                                description:
                                    'Average elapsed time across the supported funnel path.',
                            },
                        ],
                    },
                ],
            },
        ],
    },
    {
        key: 'behavior',
        label: 'Behavior',
        description: 'Top-level analytics domain for observed session patterns.',
        summaryShort:
            'Behavior summary will be generated from scenario assignment patterns.',
        summaryFull:
            'Behavior will summarize primary and supporting session patterns derived from current scenario assignment logic without altering the existing flat page.',
        subClusters: [
            {
                key: 'scenarios',
                label: 'Scenarios',
                description:
                    'Sub-Cluster for rule-based session scenarios and supporting patterns.',
                summaryShort:
                    'Scenarios summary will be generated from scenario volume and conversion movement.',
                summaryFull:
                    'Scenarios will summarize rule-based session patterns, their conversion movement, and representative sample journeys using the current flat report.',
                flatRouteName: 'admin.analytics.scenarios.index',
                metricGroups: [
                    {
                        key: 'scenario_performance',
                        label: 'Scenario Performance',
                        description:
                            'Scenario session volume, conversion rate, and event-based duration.',
                        entityLabel: 'Scenario',
                        metrics: [
                            {
                                key: 'views',
                                label: 'Sessions',
                                description:
                                    'Observed sessions assigned to the metric group.',
                            },
                            {
                                key: 'conversion_rate',
                                label: 'Conversion Rate',
                                description:
                                    'Share of assigned sessions that converted.',
                            },
                            {
                                key: 'duration',
                                label: 'Duration',
                                description:
                                    'Observed event-based session duration for the metric group.',
                            },
                        ],
                    },
                ],
            },
        ],
    },
    {
        key: 'results',
        label: 'Conversions',
        description: 'Top-level analytics domain for outcome counts and completion results.',
        summaryShort:
            'Conversions summary will be generated from conversion totals and timing movement.',
        summaryFull:
            'Conversions will summarize conversion totals, type mix, and timing from the current flat conversions page before any nested detail pages are added.',
        subClusters: [
            {
                key: 'conversions',
                label: 'Conversions',
                description:
                    'Sub-Cluster for conversion totals, type mix, and timing.',
                summaryShort:
                    'Conversions summary will be generated from total conversions and conversion types.',
                summaryFull:
                    'Conversions will summarize total movement, conversion type mix, and time-to-conversion signals from the current flat report.',
                flatRouteName: 'admin.analytics.conversions.index',
                metricGroups: [
                    {
                        key: 'conversion_performance',
                        label: 'Conversion Performance',
                        description:
                            'Conversion outcome volume and time-to-conversion.',
                        entityLabel: 'Conversion Type',
                        metrics: [
                            {
                                key: 'submissions',
                                label: 'Conversions',
                                description:
                                    'Observed conversions recorded for the metric group.',
                            },
                            {
                                key: 'time_to_conversion',
                                label: 'Time to Conversion',
                                description:
                                    'Elapsed time from session start to first conversion where supported.',
                            },
                        ],
                    },
                ],
            },
        ],
    },
    {
        key: 'source',
        label: 'Source',
        description: 'Top-level analytics domain for attribution and source association.',
        summaryShort:
            'Source summary will be generated from attribution coverage and source movement.',
        summaryFull:
            'Source will summarize attribution coverage and top source associations using the current flat attribution page and existing attribution summaries.',
        subClusters: [
            {
                key: 'attribution',
                label: 'Attribution',
                description:
                    'Sub-Cluster for attributed and unattributed conversion source summaries.',
                summaryShort:
                    'Attribution summary will be generated from attributed and unattributed conversion movement.',
                summaryFull:
                    'Attribution will summarize how observed sources appear across attribution scopes while preserving the current flat attribution route.',
                flatRouteName: 'admin.analytics.attribution.index',
                metricGroups: [
                    {
                        key: 'attribution_performance',
                        label: 'Attribution Performance',
                        description:
                            'Attributed conversion volume and attribution coverage.',
                        entityLabel: 'Source',
                        metrics: [
                            {
                                key: 'submissions',
                                label: 'Attributed Conversions',
                                description:
                                    'Observed conversions attributed to the metric group.',
                            },
                            {
                                key: 'attribution_coverage',
                                label: 'Attribution Coverage',
                                description:
                                    'Coverage of conversion attribution for the metric group or scope.',
                            },
                        ],
                    },
                ],
            },
        ],
    },
];

const materializeSubCluster = (
    clusterKey: AnalyticsClusterKey,
    subCluster: AnalyticsSubClusterSeed,
    filters?: AnalyticsFilters,
): AnalyticsSubClusterDefinition => ({
    key: subCluster.key,
    clusterKey,
    label: subCluster.label,
    description: subCluster.description,
    summaryShort: subCluster.summaryShort,
    summaryFull: subCluster.summaryFull,
    flatRouteName: subCluster.flatRouteName,
    flatHref: buildAnalyticsFlatHref(subCluster.flatRouteName, filters),
    href: buildAnalyticsSubClusterHref(clusterKey, subCluster.key, filters),
    metricGroups: subCluster.metricGroups.map((metricGroup) => ({
        ...metricGroup,
        href: null,
    })),
});

export const getMetricsForSubCluster = (
    subCluster: Pick<AnalyticsSubClusterDefinition, 'metricGroups'>,
): AnalyticsMetricDefinition[] =>
    subCluster.metricGroups.flatMap((metricGroup) => metricGroup.metrics);

export function getAnalyticsClusters(
    filters?: AnalyticsFilters,
): AnalyticsClusterDefinition[] {
    return analyticsHierarchySeeds.map((cluster) => ({
        key: cluster.key,
        label: cluster.label,
        description: cluster.description,
        summaryShort: cluster.summaryShort,
        summaryFull: cluster.summaryFull,
        href: null,
        subClusters: cluster.subClusters.map((subCluster) =>
            materializeSubCluster(cluster.key, subCluster, filters),
        ),
    }));
}

export function getAnalyticsCluster(
    key: AnalyticsClusterKey,
    filters?: AnalyticsFilters,
): AnalyticsClusterDefinition | null {
    return (
        getAnalyticsClusters(filters).find((cluster) => cluster.key === key) ??
        null
    );
}

export function getAnalyticsSubCluster(
    clusterKey: AnalyticsClusterKey,
    subClusterKey: AnalyticsSubClusterKey,
    filters?: AnalyticsFilters,
): AnalyticsSubClusterDefinition | null {
    const cluster = getAnalyticsCluster(clusterKey, filters);

    if (!cluster) {
        return null;
    }

    return (
        cluster.subClusters.find(
            (subCluster) => subCluster.key === subClusterKey,
        ) ?? null
    );
}

export function flattenAnalyticsSubClusters(
    filters?: AnalyticsFilters,
): AnalyticsSubClusterDefinition[] {
    return getAnalyticsClusters(filters).flatMap(
        (cluster) => cluster.subClusters,
    );
}
