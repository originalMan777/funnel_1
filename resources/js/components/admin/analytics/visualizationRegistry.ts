export type AnalyticsVisualType =
    | 'number_card'
    | 'trend_line'
    | 'sparkline'
    | 'horizontal_bar'
    | 'comparison_bars'
    | 'funnel_chart'
    | 'progress_rate_bar'
    | 'donut_chart'
    | 'timeline_duration'
    | 'ranked_list'
    | 'table'
    | 'flow_diagram';

export type ApprovedAnalyticsVisualKey =
    | 'premium-donut'
    | 'half-ring-score'
    | 'funnel-flow'
    | 'range-variance'
    | 'premium-vertical-bar'
    | 'mini-report-card'
    | 'stacked-performance'
    | 'source-ranking-table';

export type MetricCardVariant =
    | 'compact'
    | 'standard'
    | 'wide'
    | 'deep';

export type MetricPurpose =
    | 'amount'
    | 'comparison'
    | 'trend'
    | 'drop_off'
    | 'part_to_whole'
    | 'distribution'
    | 'source_path'
    | 'timing'
    | 'proof';

export type VisualizationStatus = 'active' | 'limited' | 'future';

export type MetricKind =
    | 'count'
    | 'rate'
    | 'trend'
    | 'comparison'
    | 'funnel'
    | 'timing'
    | 'source_share'
    | 'conversion_mix'
    | 'ranking'
    | 'proof_table';

export type MetricLike = {
    key?: string | null;
    label: string;
    value?: string | number | null;
    helper?: string | null;
    description?: string | null;
};

export type VisualizationDefinition = {
    type: AnalyticsVisualType;
    label: string;
    purpose: MetricPurpose[];
    bestFor: string[];
    avoidFor: string[];
    defaultCardVariant: MetricCardVariant;
    status: VisualizationStatus;
};

export type MetricVisualConfig = {
    primaryVisual: AnalyticsVisualType;
    secondaryVisuals: AnalyticsVisualType[];
    cardVariant: MetricCardVariant;
};

export type ApprovedVisualDefinition = {
    key: ApprovedAnalyticsVisualKey;
    cardNumber: '01' | '05' | '11' | '12' | '14' | '16' | '20' | '23';
    label: string;
    cardVariant: MetricCardVariant;
};

export type MetricVisualContext = {
    clusterKey?: string | null;
    clusterLabel?: string | null;
    subClusterKey?: string | null;
    subClusterLabel?: string | null;
    metricGroupKey?: string | null;
    metricGroupLabel?: string | null;
    metricKey?: string | null;
    metricLabel?: string | null;
};

export type ApprovedVisualMapping = MetricVisualContext & {
    visual: ApprovedAnalyticsVisualKey;
};

export const approvedVisualRegistry: Record<
    ApprovedAnalyticsVisualKey,
    ApprovedVisualDefinition
> = {
    'premium-donut': {
        key: 'premium-donut',
        cardNumber: '01',
        label: 'Premium Donut',
        cardVariant: 'standard',
    },
    'half-ring-score': {
        key: 'half-ring-score',
        cardNumber: '11',
        label: 'Half-Ring Score',
        cardVariant: 'standard',
    },
    'funnel-flow': {
        key: 'funnel-flow',
        cardNumber: '05',
        label: 'Funnel Flow',
        cardVariant: 'deep',
    },
    'range-variance': {
        key: 'range-variance',
        cardNumber: '16',
        label: 'Range Variance',
        cardVariant: 'standard',
    },
    'premium-vertical-bar': {
        key: 'premium-vertical-bar',
        cardNumber: '12',
        label: 'Premium Vertical Bar',
        cardVariant: 'standard',
    },
    'mini-report-card': {
        key: 'mini-report-card',
        cardNumber: '23',
        label: 'Mini Report Card',
        cardVariant: 'compact',
    },
    'stacked-performance': {
        key: 'stacked-performance',
        cardNumber: '14',
        label: 'Stacked Performance',
        cardVariant: 'wide',
    },
    'source-ranking-table': {
        key: 'source-ranking-table',
        cardNumber: '20',
        label: 'Source Ranking Table',
        cardVariant: 'wide',
    },
};

export const approvedVisualMappings: ApprovedVisualMapping[] = [
    { clusterKey: 'traffic', subClusterKey: 'ctas', metricGroupKey: 'cta_performance', metricKey: 'ctr', visual: 'half-ring-score' },
    { clusterKey: 'traffic', subClusterKey: 'pages', metricGroupKey: 'page_performance', metricKey: 'conversion_rate', visual: 'half-ring-score' },
    { clusterKey: 'traffic', subClusterKey: 'ctas', metricGroupKey: 'cta_performance', metricKey: 'conversion_rate', visual: 'half-ring-score' },
    { clusterKey: 'capture', subClusterKey: 'popups', metricGroupKey: 'popup_lifecycle', metricKey: 'open_rate', visual: 'half-ring-score' },
    { clusterKey: 'flow', subClusterKey: 'funnels', metricGroupKey: 'funnel_performance', metricKey: 'completion_rate', visual: 'funnel-flow' },
    { clusterKey: 'flow', subClusterKey: 'funnels', metricGroupKey: 'funnel_performance', metricKey: 'drop_off', visual: 'funnel-flow' },
    { clusterKey: 'traffic', subClusterKey: 'pages', metricGroupKey: 'page_performance', metricKey: 'time_to_conversion', visual: 'range-variance' },
    { clusterKey: 'traffic', subClusterKey: 'ctas', metricGroupKey: 'cta_performance', metricKey: 'time_to_conversion', visual: 'range-variance' },
    { clusterKey: 'results', subClusterKey: 'conversions', metricGroupKey: 'conversion_performance', metricKey: 'time_to_conversion', visual: 'range-variance' },
    { clusterKey: 'source', subClusterKey: 'attribution', metricGroupKey: 'attribution_performance', metricKey: 'attribution_coverage', visual: 'premium-donut' },
    { clusterKey: 'traffic', subClusterKey: 'pages', metricGroupKey: 'page_performance', metricKey: 'views', visual: 'premium-vertical-bar' },
    { clusterKey: 'traffic', subClusterKey: 'ctas', metricGroupKey: 'cta_performance', metricKey: 'views', visual: 'premium-vertical-bar' },
    { clusterKey: 'traffic', subClusterKey: 'ctas', metricGroupKey: 'cta_performance', metricKey: 'clicks', visual: 'premium-vertical-bar' },
    { clusterKey: 'capture', subClusterKey: 'lead_boxes', metricGroupKey: 'lead_box_lifecycle', metricKey: 'views', visual: 'mini-report-card' },
    { clusterKey: 'capture', subClusterKey: 'lead_boxes', metricGroupKey: 'lead_box_lifecycle', metricKey: 'clicks', visual: 'mini-report-card' },
    { clusterKey: 'capture', subClusterKey: 'lead_boxes', metricGroupKey: 'lead_box_lifecycle', metricKey: 'submissions', visual: 'mini-report-card' },
    { clusterKey: 'capture', subClusterKey: 'popups', metricGroupKey: 'popup_lifecycle', metricKey: 'views', visual: 'mini-report-card' },
    { clusterKey: 'capture', subClusterKey: 'popups', metricGroupKey: 'popup_lifecycle', metricKey: 'submissions', visual: 'mini-report-card' },
    { clusterKey: 'capture', subClusterKey: 'popups', metricGroupKey: 'popup_lifecycle', metricKey: 'dismissals', visual: 'mini-report-card' },
    { clusterKey: 'results', subClusterKey: 'conversions', metricGroupKey: 'conversion_performance', metricKey: 'submissions', visual: 'premium-donut' },
    { clusterKey: 'capture', subClusterKey: 'lead_boxes', metricGroupKey: 'lead_box_lifecycle', metricKey: 'failures', visual: 'mini-report-card' },
    { clusterKey: 'capture', subClusterKey: 'lead_boxes', metricGroupKey: 'lead_box_lifecycle', metricKey: 'duration', visual: 'range-variance' },
    { clusterKey: 'capture', subClusterKey: 'popups', metricGroupKey: 'popup_lifecycle', metricKey: 'duration', visual: 'range-variance' },
    { clusterKey: 'flow', subClusterKey: 'funnels', metricGroupKey: 'funnel_performance', metricKey: 'duration', visual: 'range-variance' },
    { clusterKey: 'behavior', subClusterKey: 'scenarios', metricGroupKey: 'scenario_performance', metricKey: 'views', visual: 'stacked-performance' },
    { clusterKey: 'behavior', subClusterKey: 'scenarios', metricGroupKey: 'scenario_performance', metricKey: 'conversion_rate', visual: 'stacked-performance' },
    { clusterKey: 'behavior', subClusterKey: 'scenarios', metricGroupKey: 'scenario_performance', metricKey: 'duration', visual: 'range-variance' },
    { clusterKey: 'source', subClusterKey: 'attribution', metricGroupKey: 'attribution_performance', metricKey: 'submissions', visual: 'source-ranking-table' },
];

export const excludedVisualizationTypes = [
    'scatter_plot',
    'bubble_chart',
    'radar_chart',
    'gantt_chart',
    'treemap',
    'box_plot',
    'waterfall_chart',
    'geospatial_map',
    'histogram',
] as const;

export const visualizationRegistry: Record<
    AnalyticsVisualType,
    VisualizationDefinition
> = {
    number_card: {
        type: 'number_card',
        label: 'Number Card',
        purpose: ['amount'],
        bestFor: ['clicks', 'views', 'submissions', 'conversions', 'totals'],
        avoidFor: ['flows', 'drop-off chains', 'part-to-whole reads'],
        defaultCardVariant: 'compact',
        status: 'active',
    },
    trend_line: {
        type: 'trend_line',
        label: 'Trend Line',
        purpose: ['trend', 'comparison'],
        bestFor: ['over-time movement', 'trend direction', 'performance shifts'],
        avoidFor: ['single totals', 'one-step funnel reads'],
        defaultCardVariant: 'wide',
        status: 'active',
    },
    sparkline: {
        type: 'sparkline',
        label: 'Sparkline',
        purpose: ['trend'],
        bestFor: ['compact over-time context', 'headline trend support'],
        avoidFor: ['multi-series comparisons', 'complex breakdowns'],
        defaultCardVariant: 'compact',
        status: 'active',
    },
    horizontal_bar: {
        type: 'horizontal_bar',
        label: 'Horizontal Bar',
        purpose: ['comparison', 'proof'],
        bestFor: ['ranked comparisons', 'ordered category performance'],
        avoidFor: ['timing reads', 'path analysis'],
        defaultCardVariant: 'wide',
        status: 'active',
    },
    comparison_bars: {
        type: 'comparison_bars',
        label: 'Comparison Bars',
        purpose: ['comparison', 'distribution'],
        bestFor: ['rate comparisons', 'coverage comparisons', 'side-by-side deltas'],
        avoidFor: ['funnel loss', 'journey flows'],
        defaultCardVariant: 'wide',
        status: 'active',
    },
    funnel_chart: {
        type: 'funnel_chart',
        label: 'Funnel Chart',
        purpose: ['drop_off', 'comparison'],
        bestFor: ['step completion', 'drop-off concentration', 'journey stage loss'],
        avoidFor: ['simple totals', 'part-to-whole share'],
        defaultCardVariant: 'deep',
        status: 'active',
    },
    progress_rate_bar: {
        type: 'progress_rate_bar',
        label: 'Progress Rate Bar',
        purpose: ['comparison', 'distribution'],
        bestFor: ['rates', 'completion reads', 'coverage progress'],
        avoidFor: ['multi-step funnels', 'path transitions'],
        defaultCardVariant: 'standard',
        status: 'active',
    },
    donut_chart: {
        type: 'donut_chart',
        label: 'Donut Chart',
        purpose: ['part_to_whole', 'distribution'],
        bestFor: ['attribution share', 'conversion type breakdown', 'part-of-whole reads'],
        avoidFor: ['default comparisons', 'trend analysis', 'funnel analysis'],
        defaultCardVariant: 'standard',
        status: 'limited',
    },
    timeline_duration: {
        type: 'timeline_duration',
        label: 'Timeline Duration',
        purpose: ['timing', 'comparison'],
        bestFor: ['time to conversion', 'elapsed duration', 'session timing'],
        avoidFor: ['share breakdowns', 'part-to-whole reads'],
        defaultCardVariant: 'standard',
        status: 'active',
    },
    ranked_list: {
        type: 'ranked_list',
        label: 'Ranked List',
        purpose: ['comparison', 'proof'],
        bestFor: ['top performers', 'priority rankings', 'operator review lists'],
        avoidFor: ['timelines', 'flow diagrams'],
        defaultCardVariant: 'wide',
        status: 'active',
    },
    table: {
        type: 'table',
        label: 'Table',
        purpose: ['proof', 'comparison'],
        bestFor: ['auditable detail', 'evidence-heavy comparisons', 'raw metric proof'],
        avoidFor: ['headline KPI cards', 'simple single-value reads'],
        defaultCardVariant: 'wide',
        status: 'active',
    },
    flow_diagram: {
        type: 'flow_diagram',
        label: 'Flow Diagram',
        purpose: ['source_path'],
        bestFor: ['user journeys', 'path diagrams', 'scenario transitions'],
        avoidFor: ['default metric cards', 'simple comparisons', 'totals'],
        defaultCardVariant: 'deep',
        status: 'future',
    },
};

export const metricKindVisualMap: Record<MetricKind, MetricVisualConfig> = {
    count: {
        primaryVisual: 'number_card',
        secondaryVisuals: ['sparkline', 'ranked_list'],
        cardVariant: 'compact',
    },
    rate: {
        primaryVisual: 'progress_rate_bar',
        secondaryVisuals: ['comparison_bars', 'number_card'],
        cardVariant: 'standard',
    },
    trend: {
        primaryVisual: 'trend_line',
        secondaryVisuals: ['sparkline', 'table'],
        cardVariant: 'wide',
    },
    comparison: {
        primaryVisual: 'comparison_bars',
        secondaryVisuals: ['horizontal_bar', 'table'],
        cardVariant: 'wide',
    },
    funnel: {
        primaryVisual: 'funnel_chart',
        secondaryVisuals: ['comparison_bars', 'table'],
        cardVariant: 'deep',
    },
    timing: {
        primaryVisual: 'timeline_duration',
        secondaryVisuals: ['comparison_bars', 'table'],
        cardVariant: 'standard',
    },
    source_share: {
        primaryVisual: 'donut_chart',
        secondaryVisuals: ['comparison_bars', 'ranked_list'],
        cardVariant: 'standard',
    },
    conversion_mix: {
        primaryVisual: 'donut_chart',
        secondaryVisuals: ['horizontal_bar', 'table'],
        cardVariant: 'standard',
    },
    ranking: {
        primaryVisual: 'ranked_list',
        secondaryVisuals: ['horizontal_bar', 'table'],
        cardVariant: 'wide',
    },
    proof_table: {
        primaryVisual: 'table',
        secondaryVisuals: ['ranked_list', 'comparison_bars'],
        cardVariant: 'wide',
    },
};

export function getVisualizationDefinition(type: AnalyticsVisualType) {
    return visualizationRegistry[type];
}

export function getApprovedVisualDefinition(type: ApprovedAnalyticsVisualKey) {
    return approvedVisualRegistry[type];
}

const normalizeToken = (value?: string | null) =>
    (value ?? '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');

const contextMatches = (
    mapping: ApprovedVisualMapping,
    context: MetricVisualContext,
) => {
    if (mapping.metricKey !== normalizeToken(context.metricKey)) {
        return false;
    }

    if (
        mapping.clusterKey &&
        mapping.clusterKey !== normalizeToken(context.clusterKey)
    ) {
        return false;
    }

    if (
        mapping.subClusterKey &&
        mapping.subClusterKey !== normalizeToken(context.subClusterKey)
    ) {
        return false;
    }

    if (
        mapping.metricGroupKey &&
        mapping.metricGroupKey !== normalizeToken(context.metricGroupKey)
    ) {
        return false;
    }

    return true;
};

export function resolveApprovedVisual(
    context: MetricVisualContext,
): ApprovedVisualDefinition | null {
    const normalizedContext = {
        ...context,
        clusterKey: normalizeToken(context.clusterKey),
        subClusterKey: normalizeToken(context.subClusterKey),
        metricGroupKey: normalizeToken(context.metricGroupKey),
        metricKey: normalizeToken(context.metricKey),
    };
    const exactMatch = approvedVisualMappings.find((mapping) =>
        contextMatches(mapping, normalizedContext),
    );

    if (exactMatch) {
        return approvedVisualRegistry[exactMatch.visual];
    }

    const contextualMatch = approvedVisualMappings.find(
        (mapping) =>
            mapping.metricKey === normalizedContext.metricKey &&
            mapping.clusterKey === normalizedContext.clusterKey &&
            mapping.subClusterKey === normalizedContext.subClusterKey,
    );

    return contextualMatch ? approvedVisualRegistry[contextualMatch.visual] : null;
}

export function inferMetricKind(metric: MetricLike): MetricKind {
    const fingerprint = `${metric.key ?? ''} ${metric.label}`.toLowerCase();

    if (fingerprint.includes('trend')) {
        return 'trend';
    }

    if (
        fingerprint.includes('source') ||
        fingerprint.includes('attribution') ||
        fingerprint.includes('touch')
    ) {
        return fingerprint.includes('share') || fingerprint.includes('coverage')
            ? 'source_share'
            : 'comparison';
    }

    if (
        fingerprint.includes('conversion type') ||
        fingerprint.includes('conversion mix') ||
        fingerprint.includes('breakdown')
    ) {
        return 'conversion_mix';
    }

    if (
        fingerprint.includes('drop') ||
        fingerprint.includes('funnel') ||
        fingerprint.includes('completion')
    ) {
        return 'funnel';
    }

    if (
        fingerprint.includes('time') ||
        fingerprint.includes('duration') ||
        fingerprint.includes('elapsed')
    ) {
        return 'timing';
    }

    if (
        fingerprint.includes('rate') ||
        fingerprint.includes('ctr') ||
        fingerprint.includes('coverage') ||
        fingerprint.includes('share')
    ) {
        return fingerprint.includes('share') ? 'source_share' : 'rate';
    }

    if (
        fingerprint.includes('compare') ||
        fingerprint.includes('versus') ||
        fingerprint.includes('vs')
    ) {
        return 'comparison';
    }

    if (
        fingerprint.includes('top') ||
        fingerprint.includes('rank') ||
        fingerprint.includes('leader')
    ) {
        return 'ranking';
    }

    if (
        fingerprint.includes('table') ||
        fingerprint.includes('proof') ||
        fingerprint.includes('detail')
    ) {
        return 'proof_table';
    }

    return 'count';
}

export function getVisualConfigForMetric(metric: MetricLike): MetricVisualConfig {
    return metricKindVisualMap[inferMetricKind(metric)];
}
