import type { Component } from 'vue';
import VisualVolumeBar from '@/components/admin/analytics/visuals/VisualVolumeBar.vue';
import VisualBigNumberCard from '@/components/admin/analytics/visuals/VisualBigNumberCard.vue';
import VisualHalfRingScore from '@/components/admin/analytics/visuals/VisualHalfRingScore.vue';
import VisualHalfRingScoreV2 from '@/components/admin/analytics/visuals/VisualHalfRingScoreV2.vue';

/* =========================
   TYPES
========================= */

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
    | 'big-number-card'
    | 'volume-bar'
    | 'premium-donut'
    | 'half-ring-score'
    | 'half-ring-score-v2'
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
    approvedVisualKey?: ApprovedAnalyticsVisualKey | null;
};

export type MetricVisualConfig = {
    primaryVisual: AnalyticsVisualType;
    secondaryVisuals: AnalyticsVisualType[];
    cardVariant: MetricCardVariant;
};

export type MetricVisualContext = {
    clusterKey?: string | null;
    clusterLabel?: string | null;
    subClusterKey?: string | null;
    subClusterLabel?: string | null;
    metricGroupKey?: string | null;
    metricGroupLabel?: string | null;
    groupKey?: string | null;
    metricKey?: string | null;
    metricLabel?: string | null;
};

type ApprovedVisualStatus = 'approved' | 'testing' | 'hidden';

type ApprovedVisualClassification = 'real' | 'weak' | 'fake';

type ApprovedVisualBaseDefinition = {
    key: ApprovedAnalyticsVisualKey;
    cardNumber: string;
    label: string;
    component?: Component;
    category: string;
    complexity: 'simple' | 'medium' | 'advanced';
    cardVariant: MetricCardVariant;
};

export type ApprovedVisualDefinition = ApprovedVisualBaseDefinition & {
    classification: ApprovedVisualClassification;
    status: ApprovedVisualStatus;
    reason: string;
};

type ApprovedVisualMapping = MetricVisualContext & {
    visual: ApprovedAnalyticsVisualKey;
};

/* =========================
   BASE REGISTRY
========================= */

const approvedVisualRegistryBase: Record<
    ApprovedAnalyticsVisualKey,
    ApprovedVisualBaseDefinition
> = {
    'big-number-card': {
        key: 'big-number-card',
        cardNumber: '00',
        label: 'Big Number Card',
        category: 'number',
        complexity: 'simple',
        cardVariant: 'compact',
        component: VisualBigNumberCard,
    },

    'volume-bar': {
        key: 'volume-bar',
        cardNumber: 'V1',
        label: 'Volume Bar',
        category: 'bar',
        complexity: 'simple',
        cardVariant: 'standard',
        component: VisualVolumeBar,
    },

    'half-ring-score': {
        key: 'half-ring-score',
        cardNumber: '11',
        label: 'Half-Ring Score',
        category: 'gauge',
        complexity: 'medium',
        cardVariant: 'standard',
        component: VisualHalfRingScore,
    },

    'premium-donut': {
        key: 'premium-donut',
        cardNumber: '01',
        label: 'Premium Donut',
        category: 'donut',
        complexity: 'medium',
        cardVariant: 'standard',
    },

    'funnel-flow': {
        key: 'funnel-flow',
        cardNumber: '05',
        label: 'Funnel Flow',
        category: 'flow',
        complexity: 'advanced',
        cardVariant: 'deep',
    },

    'range-variance': {
        key: 'range-variance',
        cardNumber: '16',
        label: 'Range Variance',
        category: 'range',
        complexity: 'medium',
        cardVariant: 'standard',
    },

    'half-ring-score-v2': {
        key: 'half-ring-score-v2',
        cardNumber: 'T2',
        label: 'Half Ring Score V2',
        category: 'gauge',
        complexity: 'medium',
        cardVariant: 'standard',
        component: VisualHalfRingScoreV2,
    },

    'premium-vertical-bar': {
        key: 'premium-vertical-bar',
        cardNumber: '12',
        label: 'Premium Vertical Bar',
        category: 'bar',
        complexity: 'simple',
        cardVariant: 'standard',
    },

    'mini-report-card': {
        key: 'mini-report-card',
        cardNumber: '23',
        label: 'Mini Report Card',
        category: 'card',
        complexity: 'simple',
        cardVariant: 'compact',
    },

    'stacked-performance': {
        key: 'stacked-performance',
        cardNumber: '14',
        label: 'Stacked Performance',
        category: 'bar',
        complexity: 'advanced',
        cardVariant: 'wide',
    },

    'source-ranking-table': {
        key: 'source-ranking-table',
        cardNumber: '20',
        label: 'Source Ranking Table',
        category: 'table',
        complexity: 'advanced',
        cardVariant: 'wide',
    },
};

/* =========================
   GOVERNANCE
========================= */

const visualGovernance: Record<
    ApprovedAnalyticsVisualKey,
    {
        classification: ApprovedVisualClassification;
        status: ApprovedVisualStatus;
        reason: string;
    }
> = {
    'big-number-card': {
        classification: 'real',
        status: 'approved',
        reason: 'Displays raw value.',
    },
    'volume-bar': {
        classification: 'real',
        status: 'approved',
        reason: 'Volume scaling.',
    },
    'half-ring-score': {
        classification: 'real',
        status: 'approved',
        reason: 'Position on weak→strong spectrum.',
    },
    'half-ring-score-v2': {
        classification: 'real',
        status: 'testing',
        reason: 'Testing a stronger half-ring score treatment without replacing the approved original.',
    },
    'premium-donut': { classification: 'real', status: 'approved', reason: '' },
    'funnel-flow': { classification: 'real', status: 'approved', reason: '' },
    'range-variance': { classification: 'real', status: 'approved', reason: '' },
    'premium-vertical-bar': { classification: 'real', status: 'approved', reason: '' },
    'mini-report-card': { classification: 'real', status: 'approved', reason: '' },
    'stacked-performance': { classification: 'real', status: 'approved', reason: '' },
    'source-ranking-table': { classification: 'real', status: 'approved', reason: '' },
};

const withGovernance = (
    visual: (typeof approvedVisualRegistryBase)[ApprovedAnalyticsVisualKey],
): ApprovedVisualDefinition => ({
    ...visual,
    classification: visualGovernance[visual.key].classification,
    status: visualGovernance[visual.key].status,
    reason: visualGovernance[visual.key].reason,
});

export const approvedVisualRegistry: Record<string, ApprovedVisualDefinition> = Object.fromEntries(
    Object.values(approvedVisualRegistryBase).map((visual) => [
        visual.key,
        withGovernance(visual),
    ]),
);

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

/* =========================
   HELPERS
========================= */

export function getApprovedVisualDefinition(
    key: ApprovedAnalyticsVisualKey | null | undefined,
    _mode?: 'production' | 'testing',
) {
    if (!key) return null;

    return approvedVisualRegistry[key] ?? null;
}

export function resolveApprovedVisual(
    keyOrContext: ApprovedAnalyticsVisualKey | MetricVisualContext | null | undefined,
) {
    if (!keyOrContext) return null;

    if (typeof keyOrContext === 'string') {
        const visual = approvedVisualRegistry[keyOrContext];

        if (!visual) return null;

        return visual.status === 'approved' ? visual : null;
    }

    const normalizedContext = {
        ...keyOrContext,
        clusterKey: normalizeToken(keyOrContext.clusterKey),
        subClusterKey: normalizeToken(keyOrContext.subClusterKey),
        metricGroupKey: normalizeToken(keyOrContext.metricGroupKey ?? keyOrContext.groupKey),
        metricKey: normalizeToken(keyOrContext.metricKey),
    };
    const exactMatch = approvedVisualMappings.find((mapping) =>
        contextMatches(mapping, normalizedContext),
    );

    if (exactMatch) {
        return approvedVisualRegistry[exactMatch.visual] ?? null;
    }

    const contextualMatch = approvedVisualMappings.find(
        (mapping) =>
            mapping.metricKey === normalizedContext.metricKey &&
            mapping.clusterKey === normalizedContext.clusterKey &&
            mapping.subClusterKey === normalizedContext.subClusterKey,
    );

    return contextualMatch ? approvedVisualRegistry[contextualMatch.visual] ?? null : null;
}

export const availableTestingVisuals = Object.values(approvedVisualRegistry).filter(
    (v) => v.status !== 'hidden',
);

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

export function getVisualConfigForMetric(metric: any) {
    if (!metric) return null;

    // If metric already has an approved visual, use it
    if (metric.approvedVisualKey) {
        return approvedVisualRegistry[metric.approvedVisualKey] ?? null;
    }

    // Fallback: resolve based on context
    return resolveApprovedVisual({
        metricKey: metric.key ?? null,
        groupKey: metric.groupKey ?? null,
        subClusterKey: metric.subClusterKey ?? null,
        clusterKey: metric.clusterKey ?? null,
    });
}
