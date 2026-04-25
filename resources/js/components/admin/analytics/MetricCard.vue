<script setup lang="ts">
import { computed } from 'vue';
import { formatDuration } from '@/components/admin/analytics/formatters';
import {
    getApprovedVisualDefinition,
    getVisualConfigForMetric,
    resolveApprovedVisual,
    type ApprovedAnalyticsVisualKey,
    type AnalyticsVisualType,
    type MetricCardVariant,
    type MetricVisualContext,
} from '@/components/admin/analytics/visualizationRegistry';

type Metric = {
    key?: string;
    label: string;
    value: string | number;
    displayValue?: string | number | null;
    helper?: string | null;
    description?: string | null;
    dataSource?: string | null;
    status?: 'good' | 'warning' | 'poor' | 'neutral' | string | null;
    statusLabel?: string | null;
    trendLabel?: string | null;
    delta?: string | number | null;
    insight?: string | null;
    recommendation?: string | null;
    trend?: Array<Record<string, string | number>> | null;
    series?:
        | Array<{ key: string; label?: string; colorClass?: string }>
        | Array<number | string>
        | null;
    relatedMetrics?: Array<{
        label: string;
        value: string | number;
        helper?: string | null;
    }>;
};

const props = withDefaults(
    defineProps<{
        metric: Metric;
        variant?: MetricCardVariant;
        visualType?: AnalyticsVisualType;
        approvedVisualType?: ApprovedAnalyticsVisualKey;
        visualContext?: MetricVisualContext;
        clickable?: boolean;
    }>(),
    {
        clickable: false,
    },
);

const emit = defineEmits<{
    select: [metric: Metric];
}>();

const formattedValue = computed(() => {
    const value = String(props.metric.displayValue ?? props.metric.value ?? '—');

    return props.metric.helper === 'seconds' && value !== '—'
        ? formatDuration(Number(value))
        : value;
});

const breakdown = computed(
    () =>
        props.metric.description ||
        props.metric.helper ||
        'Detailed metric guidance will appear once this metric is mapped.',
);

const contextBadge = computed(() => {
    if (props.metric.helper && props.metric.helper.trim() && props.metric.helper !== 'seconds') {
        return props.metric.helper.trim();
    }

    return null;
});

const supportingContext = computed(() => contextBadge.value ?? 'Context pending');
const statusLabel = computed(() => props.metric.statusLabel ?? 'Baseline');
const dataSourceLabel = computed(() => {
    if (props.metric.dataSource === 'real') {
        return 'Real report';
    }

    if (props.metric.dataSource === 'local_demo') {
        return 'Local demo';
    }

    return 'No source';
});
const statusBadgeClasses = computed(() => {
    const status = props.metric.status ?? 'neutral';

    const classes: Record<string, string> = {
        good: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        warning: 'border-amber-200 bg-amber-50 text-amber-700',
        poor: 'border-rose-200 bg-rose-50 text-rose-700',
        neutral: 'border-slate-200 bg-white/85 text-slate-500',
    };

    return classes[status] ?? classes.neutral;
});

const relatedMetric = computed(() => props.metric.relatedMetrics?.[0] ?? null);
const metricSvgIdBase = computed(() =>
    (props.metric.key || props.metric.label || 'metric-card')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, ''),
);

const resolvedVisualConfig = computed(() => getVisualConfigForMetric(props.metric));
const resolvedApprovedVisual = computed(() => {
    if (props.approvedVisualType) {
        return getApprovedVisualDefinition(props.approvedVisualType);
    }

    return resolveApprovedVisual({
        ...props.visualContext,
        metricKey: props.visualContext?.metricKey ?? props.metric.key,
        metricLabel: props.visualContext?.metricLabel ?? props.metric.label,
    });
});

const resolvedVariant = computed(
    () =>
        props.variant ??
        resolvedApprovedVisual.value?.cardVariant ??
        resolvedVisualConfig.value.cardVariant,
);

const resolvedVisualType = computed(
    () => props.visualType ?? resolvedVisualConfig.value.primaryVisual,
);

const isCompactVariant = computed(() => resolvedVariant.value === 'compact');
const isNumberCard = computed(() => resolvedVisualType.value === 'number_card');
const isProgressRateBar = computed(
    () => resolvedVisualType.value === 'progress_rate_bar',
);
const isHorizontalBar = computed(
    () => resolvedVisualType.value === 'horizontal_bar',
);
const isComparisonBars = computed(
    () => resolvedVisualType.value === 'comparison_bars',
);
const isSparkline = computed(() => resolvedVisualType.value === 'sparkline');
const isTrendLine = computed(() => resolvedVisualType.value === 'trend_line');
const hasApprovedVisual = computed(() => resolvedApprovedVisual.value !== null);
const usesPremiumCardSurface = computed(
    () =>
        hasApprovedVisual.value ||
        isNumberCard.value ||
        isProgressRateBar.value ||
        isHorizontalBar.value ||
        isComparisonBars.value ||
        isSparkline.value ||
        isTrendLine.value,
);

const cardClasses = computed(() => {
    const clickableClasses = props.clickable
        ? 'cursor-pointer hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-xl hover:shadow-sky-100/60 focus-visible:border-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300/70'
        : 'cursor-default';

    const variants: Record<MetricCardVariant, string> = {
        compact:
            'min-h-[14rem] rounded-[1.15rem] p-3.5',
        standard:
            'min-h-[18rem] rounded-[1.5rem] p-5',
        wide:
            'min-h-[18rem] rounded-[1.5rem] p-5',
        deep:
            'min-h-[21rem] rounded-[1.6rem] p-5',
    };

    return [
        'relative flex h-full flex-col overflow-hidden border text-left transition duration-200',
        usesPremiumCardSurface.value
            ? 'border-slate-200/80 bg-[linear-gradient(160deg,rgba(248,250,252,0.96),rgba(255,255,255,0.94)_55%,rgba(240,249,255,0.9))] shadow-[0_20px_45px_-28px_rgba(14,165,233,0.38)]'
            : 'border-slate-200 bg-slate-50/70',
        variants[resolvedVariant.value],
        clickableClasses,
    ].join(' ');
});

const visualAreaClasses = computed(() => {
    const heights: Record<MetricCardVariant, string> = {
        compact: 'min-h-[3.75rem]',
        standard: 'min-h-[5.75rem]',
        wide: 'min-h-[5.75rem]',
        deep: 'min-h-[7.5rem]',
    };

    return [
        usesPremiumCardSurface.value
            ? 'mt-5 rounded-[1.25rem] border border-white/70 bg-white/75 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] backdrop-blur'
            : 'mt-5 rounded-[1.1rem] border border-dashed border-slate-300 bg-white/80 p-4',
        heights[resolvedVariant.value],
    ].join(' ');
});

const parsePercentValue = (value: string | number) => {
    if (typeof value === 'number') {
        if (!Number.isFinite(value)) {
            return null;
        }

        if (value >= 0 && value <= 1) {
            return value * 100;
        }

        if (value >= 0 && value <= 100) {
            return value;
        }

        return null;
    }

    const trimmed = value.trim();

    if (!trimmed) {
        return null;
    }

    const hasPercentSign = trimmed.includes('%');
    const normalized = hasPercentSign ? trimmed.replace('%', '').trim() : trimmed;
    const parsed = Number(normalized);

    if (!Number.isFinite(parsed)) {
        return null;
    }

    if (hasPercentSign) {
        return parsed;
    }

    if (parsed >= 0 && parsed <= 1) {
        return parsed * 100;
    }

    if (parsed >= 0 && parsed <= 100) {
        return parsed;
    }

    return null;
};

const parsedPercent = computed(() => parsePercentValue(props.metric.value));

const percentFill = computed(() => {
    if (parsedPercent.value === null) {
        return 0;
    }

    return Math.min(Math.max(parsedPercent.value, 0), 100);
});

const approvedFill = computed(() => {
    if (parsedPercent.value !== null) {
        return percentFill.value;
    }

    if (parsedNumericValue.value === null) {
        return 0;
    }

    if (parsedNumericValue.value >= 0 && parsedNumericValue.value <= 1) {
        return Math.min(Math.max(parsedNumericValue.value * 100, 0), 100);
    }

    if (parsedNumericValue.value >= 0 && parsedNumericValue.value <= 100) {
        return Math.min(Math.max(parsedNumericValue.value, 0), 100);
    }

    return Math.min(Math.max(Math.log10(parsedNumericValue.value + 1) * 22, 12), 96);
});

const approvedStrokeDasharray = computed(() => {
    const fill = Math.min(Math.max(approvedFill.value, 0), 100);

    return `${Number((fill * 1.57).toFixed(1))} 157`;
});

const approvedBarHeight = computed(() =>
    `${Math.min(Math.max(approvedFill.value, 12), 92)}%`,
);

const approvedRows = computed(() => {
    const rows = [
        {
            label: props.metric.label,
            value: formattedValue.value,
            width: `${Math.min(Math.max(approvedFill.value, 16), 96)}%`,
        },
        ...(props.metric.relatedMetrics ?? []).slice(0, 2).map((metric) => {
            const numeric = parseNumericValue(metric.value);
            const width =
                numeric === null
                    ? 34
                    : Math.min(Math.max(Math.log10(numeric + 1) * 22, 16), 92);

            return {
                label: metric.label,
                value: formatRelatedMetricValue(metric.value, metric.helper),
                width: `${width}%`,
            };
        }),
    ];

    return rows;
});

const approvedDataStateLabel = computed(() =>
    props.metric.dataSource === 'local_demo'
        ? 'Local demo'
        : parsedNumericValue.value === null && parsedPercent.value === null
            ? 'No parsed data'
            : 'Live value',
);

const formatPercentDisplay = (value: number) => {
    const rounded = Number(value.toFixed(value % 1 === 0 ? 0 : 1));

    return `${rounded}%`;
};

const progressDisplayValue = computed(() => {
    if (parsedPercent.value === null) {
        return formattedValue.value;
    }

    return formatPercentDisplay(parsedPercent.value);
});

const parseNumericValue = (value: string | number) => {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : null;
    }

    const normalized = value.replace(/,/g, '').trim();

    if (!normalized) {
        return null;
    }

    const numeric = Number(normalized.replace('%', '').trim());

    return Number.isFinite(numeric) ? numeric : null;
};

const parsedNumericValue = computed(() => parseNumericValue(props.metric.value));

const horizontalBarFill = computed(() => {
    if (parsedPercent.value !== null) {
        return percentFill.value;
    }

    if (parsedNumericValue.value === null) {
        return 42;
    }

    if (parsedNumericValue.value >= 0 && parsedNumericValue.value <= 1) {
        return Math.min(Math.max(parsedNumericValue.value * 100, 0), 100);
    }

    if (parsedNumericValue.value >= 0 && parsedNumericValue.value <= 100) {
        return Math.min(Math.max(parsedNumericValue.value, 0), 100);
    }

    return 42;
});

const comparisonMetrics = computed(() => props.metric.relatedMetrics?.slice(0, 2) ?? []);

const numericComparisonValues = computed(() => {
    const values = [
        parsedNumericValue.value,
        ...comparisonMetrics.value.map((metric) => parseNumericValue(metric.value)),
    ].filter((value): value is number => value !== null && value >= 0);

    return values;
});

const comparisonScaleMax = computed(() => {
    if (numericComparisonValues.value.length === 0) {
        return null;
    }

    return Math.max(...numericComparisonValues.value, 0);
});

const getComparisonFill = (value: string | number) => {
    const numeric = parseNumericValue(value);

    if (numeric === null || comparisonScaleMax.value === null || comparisonScaleMax.value <= 0) {
        return 0;
    }

    return Math.min(Math.max((numeric / comparisonScaleMax.value) * 100, 0), 100);
};

const trendRows = computed(() =>
    Array.isArray(props.metric.trend) ? props.metric.trend : [],
);

const trendSeriesDefinitions = computed(() => {
    if (!Array.isArray(props.metric.series) || props.metric.series.length === 0) {
        return [];
    }

    if (
        typeof props.metric.series[0] === 'object' &&
        props.metric.series[0] !== null &&
        'key' in props.metric.series[0]
    ) {
        return props.metric.series as Array<{
            key: string;
            label?: string;
            colorClass?: string;
        }>;
    }

    return [];
});

const directSeriesValues = computed(() => {
    if (!Array.isArray(props.metric.series) || props.metric.series.length === 0) {
        return [];
    }

    if (
        typeof props.metric.series[0] === 'object' &&
        props.metric.series[0] !== null
    ) {
        return [];
    }

    return props.metric.series
        .map((value) => parseNumericValue(String(value)))
        .filter((value): value is number => value !== null);
});

const trendValues = computed(() => {
    if (trendRows.value.length > 0 && trendSeriesDefinitions.value.length > 0) {
        const primarySeries = trendSeriesDefinitions.value[0];

        return trendRows.value
            .map((row) => parseNumericValue(String(row[primarySeries.key] ?? '')))
            .filter((value): value is number => value !== null);
    }

    return directSeriesValues.value;
});

const hasTrendData = computed(() => trendValues.value.length >= 2);

const buildLinePoints = (values: number[], width: number, height: number) => {
    if (values.length === 0) {
        return '';
    }

    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const xStep = values.length > 1 ? width / (values.length - 1) : width;

    return values
        .map((value, index) => {
            const x = index * xStep;
            const y = height - ((value - min) / range) * height;

            return `${x},${Number(y.toFixed(2))}`;
        })
        .join(' ');
};

const sparklinePoints = computed(() => buildLinePoints(trendValues.value, 100, 36));
const trendLinePoints = computed(() => buildLinePoints(trendValues.value, 100, 56));

const sparklineLastPoint = computed(() => {
    if (!hasTrendData.value) {
        return null;
    }

    const points = sparklinePoints.value.split(' ');
    const lastPoint = points[points.length - 1];

    if (!lastPoint) {
        return null;
    }

    const [x, y] = lastPoint.split(',').map(Number);

    return Number.isFinite(x) && Number.isFinite(y) ? { x, y } : null;
});

const trendLineLastPoint = computed(() => {
    if (!hasTrendData.value) {
        return null;
    }

    const points = trendLinePoints.value.split(' ');
    const lastPoint = points[points.length - 1];

    if (!lastPoint) {
        return null;
    }

    const [x, y] = lastPoint.split(',').map(Number);

    return Number.isFinite(x) && Number.isFinite(y) ? { x, y } : null;
});

const formatRelatedMetricValue = (
    value: string | number,
    helper?: string | null,
) => {
    const stringValue = String(value ?? '—');

    return helper === 'seconds' && stringValue !== '—'
        ? formatDuration(Number(value))
        : stringValue;
};

const onSelect = () => {
    if (props.clickable) {
        emit('select', props.metric);
    }
};
</script>

<template>
    <button
        type="button"
        :class="cardClasses"
        @click="onSelect"
    >
        <div
            v-if="usesPremiumCardSurface"
            class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.2),transparent_60%)]"
        />

        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-semibold tracking-[0.18em] text-slate-500 uppercase">
                    Metric
                </p>
                <h4
                    class="mt-1.5 font-semibold text-slate-950"
                    :class="isCompactVariant ? 'text-base leading-5' : 'text-lg'"
                >
                    {{ metric.label }}
                </h4>
            </div>
            <span
                v-if="clickable"
                class="inline-flex shrink-0 items-center rounded-full border border-white/80 bg-white/85 px-3 py-1 text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase shadow-sm"
            >
                Focus
            </span>
            <span
                v-else-if="resolvedApprovedVisual"
                class="inline-flex shrink-0 items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold tracking-[0.16em] text-sky-700 uppercase shadow-sm"
            >
                {{ resolvedApprovedVisual.cardNumber }}
            </span>
        </div>

        <div
            class="flex flex-wrap gap-2"
            :class="isCompactVariant ? 'mt-2' : 'mt-3'"
        >
            <span
                class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] uppercase"
                :class="statusBadgeClasses"
            >
                {{ statusLabel }}
            </span>
            <span
                v-if="!isCompactVariant"
                class="inline-flex items-center rounded-full border border-slate-200 bg-white/85 px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase"
            >
                {{ dataSourceLabel }}
            </span>
            <span
                v-if="metric.trendLabel"
                class="inline-flex items-center rounded-full border border-slate-200 bg-white/85 px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase"
            >
                {{ metric.trendLabel }}
            </span>
        </div>

        <div
            v-if="resolvedApprovedVisual"
            class="relative flex flex-1 flex-col"
            :class="isCompactVariant ? 'mt-3' : 'mt-4'"
        >
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full border border-sky-200/80 bg-sky-50/90 px-2.5 py-1 text-[10px] font-semibold tracking-[0.14em] text-sky-700 uppercase">
                    {{ resolvedApprovedVisual.cardNumber }} {{ resolvedApprovedVisual.label }}
                </span>
                <span
                    v-if="!isCompactVariant"
                    class="inline-flex items-center rounded-full border border-slate-200 bg-white/85 px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase"
                >
                    {{ approvedDataStateLabel }}
                </span>
            </div>

            <div
                class="font-semibold text-slate-950"
                :class="isCompactVariant ? 'mt-3 text-3xl tracking-tight' : 'mt-4 text-5xl tracking-[-0.04em]'"
            >
                {{ resolvedApprovedVisual.key === 'half-ring-score' && parsedPercent !== null ? progressDisplayValue : formattedValue }}
            </div>

            <p
                v-if="metric.insight"
                class="mt-2 font-medium text-slate-600"
                :class="isCompactVariant ? 'line-clamp-2 text-xs leading-5' : 'text-sm leading-6'"
            >
                {{ metric.insight }}
            </p>

            <div :class="visualAreaClasses">
                <div
                    v-if="resolvedApprovedVisual.key === 'half-ring-score'"
                    class="flex items-center justify-center"
                >
                    <svg
                        viewBox="0 0 180 110"
                        class="w-full max-w-[13rem]"
                        :class="isCompactVariant ? 'h-20' : 'h-28'"
                        aria-hidden="true"
                    >
                        <path d="M 24 92 A 66 66 0 0 1 156 92" fill="none" stroke="rgba(203,213,225,0.85)" stroke-width="16" stroke-linecap="round" />
                        <path
                            d="M 24 92 A 66 66 0 0 1 156 92"
                            fill="none"
                            :stroke="parsedPercent === null ? 'rgba(148,163,184,0.65)' : 'rgba(14,165,233,0.95)'"
                            stroke-width="16"
                            stroke-linecap="round"
                            :stroke-dasharray="approvedStrokeDasharray"
                        />
                        <text x="90" y="78" text-anchor="middle" class="fill-slate-950 text-[24px] font-semibold">
                            {{ parsedPercent === null ? '—' : progressDisplayValue }}
                        </text>
                    </svg>
                </div>

                <div
                    v-else-if="resolvedApprovedVisual.key === 'premium-donut'"
                    class="flex items-center justify-center"
                >
                    <svg
                        viewBox="0 0 150 150"
                        :class="isCompactVariant ? 'h-24 w-24' : 'h-32 w-32'"
                        aria-hidden="true"
                    >
                        <circle cx="75" cy="75" r="48" fill="none" stroke="rgba(203,213,225,0.85)" stroke-width="18" />
                        <circle
                            cx="75"
                            cy="75"
                            r="48"
                            fill="none"
                            :stroke="parsedNumericValue === null && parsedPercent === null ? 'rgba(148,163,184,0.65)' : 'rgba(14,165,233,0.95)'"
                            stroke-width="18"
                            stroke-linecap="round"
                            :stroke-dasharray="`${Number((approvedFill * 3.02).toFixed(1))} 302`"
                            transform="rotate(-90 75 75)"
                        />
                        <circle cx="75" cy="75" r="29" fill="white" />
                        <text x="75" y="80" text-anchor="middle" class="fill-slate-950 text-[20px] font-semibold">
                            {{ formattedValue }}
                        </text>
                    </svg>
                </div>

                <div
                    v-else-if="resolvedApprovedVisual.key === 'funnel-flow'"
                    class="space-y-2"
                >
                    <div class="mx-auto h-4 w-full max-w-[15rem] rounded-md bg-sky-300" />
                    <div class="mx-auto h-4 w-5/6 rounded-md bg-sky-400" />
                    <div
                        class="mx-auto h-4 rounded-md"
                        :class="parsedNumericValue === null ? 'w-3/5 bg-slate-300' : 'bg-sky-500'"
                        :style="{ width: `${Math.min(Math.max(approvedFill, 24), 72)}%` }"
                    />
                    <div class="mx-auto h-4 w-2/5 rounded-md bg-sky-700/80" />
                </div>

                <div
                    v-else-if="resolvedApprovedVisual.key === 'range-variance'"
                    class="space-y-4"
                >
                    <div class="flex items-center justify-between text-xs font-medium text-slate-500">
                        <span>Low</span>
                        <span>{{ formattedValue }}</span>
                        <span>High</span>
                    </div>
                    <div class="relative h-3 rounded-full bg-slate-200">
                        <div class="absolute inset-y-0 left-[12%] right-[18%] rounded-full bg-gradient-to-r from-amber-300 via-sky-300 to-emerald-300" />
                        <div
                            class="absolute top-1/2 h-5 w-5 -translate-y-1/2 rounded-full border-2 border-white bg-slate-950 shadow-sm"
                            :style="{ left: `calc(${Math.min(Math.max(approvedFill, 10), 90)}% - 0.625rem)` }"
                        />
                    </div>
                </div>

                <div
                    v-else-if="resolvedApprovedVisual.key === 'premium-vertical-bar'"
                    class="flex items-end justify-center gap-3"
                    :class="isCompactVariant ? 'h-20' : 'h-28'"
                >
                    <div class="h-[38%] w-8 rounded-t-xl bg-slate-200" />
                    <div class="w-10 rounded-t-xl bg-gradient-to-t from-sky-600 to-cyan-300 shadow-[0_12px_24px_-18px_rgba(14,165,233,0.9)]" :style="{ height: approvedBarHeight }" />
                    <div class="h-[54%] w-8 rounded-t-xl bg-slate-300" />
                </div>

                <div
                    v-else-if="resolvedApprovedVisual.key === 'mini-report-card'"
                    class="grid gap-3"
                >
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase">Snapshot</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ formattedValue }}</p>
                    </div>
                    <div class="h-2 rounded-full bg-slate-200">
                        <div class="h-full rounded-full bg-sky-400" :style="{ width: `${Math.min(Math.max(approvedFill, 8), 100)}%` }" />
                    </div>
                </div>

                <div
                    v-else-if="resolvedApprovedVisual.key === 'stacked-performance'"
                    class="space-y-3"
                >
                    <div class="h-5 overflow-hidden rounded-full bg-slate-200">
                        <div class="inline-block h-full bg-sky-400" :style="{ width: `${Math.min(Math.max(approvedFill, 16), 64)}%` }" />
                        <div class="inline-block h-full bg-emerald-300" style="width: 22%" />
                        <div class="inline-block h-full bg-amber-300" style="width: 14%" />
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs font-medium text-slate-500">
                        <span>Primary</span>
                        <span>Support</span>
                        <span>Watch</span>
                    </div>
                </div>

                <div
                    v-else-if="resolvedApprovedVisual.key === 'source-ranking-table'"
                    class="space-y-2"
                >
                    <div
                        v-for="row in approvedRows"
                        :key="row.label"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2"
                    >
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="truncate font-medium text-slate-600">{{ row.label }}</span>
                            <span class="font-semibold text-slate-950">{{ row.value }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-sky-400" :style="{ width: row.width }" />
                        </div>
                    </div>
                </div>
            </div>

            <p
                class="max-w-[28ch] text-slate-600"
                :class="isCompactVariant ? 'mt-3 line-clamp-2 text-xs leading-5' : 'mt-4 text-sm leading-6'"
            >
                {{ breakdown }}
            </p>
        </div>

        <div
            v-else-if="isNumberCard"
            class="relative mt-4 flex flex-1 flex-col"
        >
            <div class="flex items-center gap-2">
                <span
                    v-if="contextBadge"
                    class="inline-flex items-center rounded-full border border-sky-200/80 bg-sky-50/90 px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] text-sky-700 uppercase"
                >
                    {{ contextBadge }}
                </span>
            </div>

            <div class="mt-4 text-5xl font-semibold tracking-[-0.04em] text-slate-950">
                {{ formattedValue }}
            </div>

            <p class="mt-3 max-w-[26ch] text-sm leading-6 text-slate-600">
                {{ breakdown }}
            </p>

            <div
                v-if="relatedMetric"
                class="mt-auto pt-5"
            >
                <div
                    class="flex items-center justify-between rounded-[1rem] border border-sky-100/80 bg-sky-50/70 px-4 py-3"
                >
                    <div>
                        <p class="text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase">
                            Related
                        </p>
                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ relatedMetric.label }}
                        </p>
                    </div>
                    <p class="text-lg font-semibold tracking-tight text-slate-950">
                        {{
                            formatRelatedMetricValue(
                                relatedMetric.value,
                                relatedMetric.helper,
                            )
                        }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-else-if="isProgressRateBar"
            class="relative mt-4 flex flex-1 flex-col"
        >
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] uppercase"
                    :class="
                        contextBadge
                            ? 'border-sky-200/80 bg-sky-50/90 text-sky-700'
                            : 'border-slate-200 bg-slate-100/80 text-slate-500'
                    "
                >
                    {{ supportingContext }}
                </span>
            </div>

            <div class="mt-4 text-5xl font-semibold tracking-[-0.04em] text-slate-950">
                {{ progressDisplayValue }}
            </div>

            <div :class="visualAreaClasses">
                <div class="space-y-4">
                    <div
                        class="h-4 overflow-hidden rounded-full bg-slate-200/80 ring-1 ring-slate-200/70"
                    >
                        <div
                            class="h-full rounded-full transition-[width] duration-500"
                            :class="
                                parsedPercent === null
                                    ? 'bg-slate-300/70'
                                    : 'bg-[linear-gradient(90deg,rgba(14,165,233,0.95),rgba(59,130,246,0.92)_48%,rgba(99,102,241,0.88))] shadow-[0_0_24px_rgba(56,189,248,0.35)]'
                            "
                            :style="{ width: `${percentFill}%` }"
                        />
                    </div>

                    <div class="flex items-center justify-between text-xs font-medium text-slate-500">
                        <span>{{ parsedPercent === null ? 'Rate unavailable' : 'Current rate' }}</span>
                        <span>{{ parsedPercent === null ? 'No parsed percentage' : formatPercentDisplay(percentFill) }}</span>
                    </div>
                </div>
            </div>

            <p class="mt-4 max-w-[28ch] text-sm leading-6 text-slate-600">
                {{ breakdown }}
            </p>
        </div>

        <div
            v-else-if="isHorizontalBar"
            class="relative mt-4 flex flex-1 flex-col"
        >
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] uppercase"
                    :class="
                        contextBadge
                            ? 'border-cyan-200/80 bg-cyan-50/90 text-cyan-700'
                            : 'border-slate-200 bg-slate-100/80 text-slate-500'
                    "
                >
                    {{ supportingContext }}
                </span>
            </div>

            <div class="mt-4 text-5xl font-semibold tracking-[-0.04em] text-slate-950">
                {{ formattedValue }}
            </div>

            <div :class="visualAreaClasses">
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-xs font-medium text-slate-500">
                        <span>Signal strength</span>
                        <span>
                            {{ parsedPercent !== null || parsedNumericValue !== null ? 'Parsed from value' : 'Neutral presentation' }}
                        </span>
                    </div>

                    <div class="relative h-4 overflow-hidden rounded-full bg-slate-200/80 ring-1 ring-slate-200/70">
                        <div
                            class="absolute inset-y-0 left-0 rounded-full bg-[linear-gradient(90deg,rgba(6,182,212,0.95),rgba(14,165,233,0.92)_46%,rgba(59,130,246,0.88))] shadow-[0_0_24px_rgba(34,211,238,0.35)] transition-[width] duration-500"
                            :style="{ width: `${horizontalBarFill}%` }"
                        />
                    </div>

                    <div class="flex items-center justify-between text-xs font-medium text-slate-400">
                        <span>Relative view</span>
                        <span>{{ parsedPercent !== null ? formatPercentDisplay(percentFill) : formattedValue }}</span>
                    </div>
                </div>
            </div>

            <p class="mt-4 max-w-[28ch] text-sm leading-6 text-slate-600">
                {{ breakdown }}
            </p>
        </div>

        <div
            v-else-if="isComparisonBars"
            class="relative mt-4 flex flex-1 flex-col"
        >
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] uppercase"
                    :class="
                        contextBadge
                            ? 'border-indigo-200/80 bg-indigo-50/90 text-indigo-700'
                            : 'border-slate-200 bg-slate-100/80 text-slate-500'
                    "
                >
                    {{ supportingContext }}
                </span>
            </div>

            <div class="mt-4 text-5xl font-semibold tracking-[-0.04em] text-slate-950">
                {{ formattedValue }}
            </div>

            <div :class="visualAreaClasses">
                <div
                    v-if="comparisonMetrics.length > 0"
                    class="space-y-3"
                >
                    <div
                        v-for="comparisonMetric in comparisonMetrics"
                        :key="comparisonMetric.label"
                        class="rounded-[1rem] border border-slate-100/90 bg-white/80 px-3 py-3 shadow-[0_8px_18px_-16px_rgba(15,23,42,0.4)]"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-sm font-medium text-slate-700">
                                {{ comparisonMetric.label }}
                            </p>
                            <p class="shrink-0 text-sm font-semibold tracking-tight text-slate-950">
                                {{
                                    formatRelatedMetricValue(
                                        comparisonMetric.value,
                                        comparisonMetric.helper,
                                    )
                                }}
                            </p>
                        </div>

                        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-200/80 ring-1 ring-slate-200/70">
                            <div
                                class="h-full rounded-full bg-[linear-gradient(90deg,rgba(99,102,241,0.95),rgba(79,70,229,0.9)_48%,rgba(37,99,235,0.85))] shadow-[0_0_20px_rgba(99,102,241,0.24)] transition-[width] duration-500"
                                :style="{ width: `${getComparisonFill(comparisonMetric.value)}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="flex min-h-full items-center justify-center rounded-[1rem] border border-dashed border-slate-200 bg-slate-50/70 px-4 py-5 text-center text-sm leading-6 text-slate-500"
                >
                    Comparison context will appear when related metrics are mapped.
                </div>
            </div>

            <p class="mt-4 max-w-[28ch] text-sm leading-6 text-slate-600">
                {{ breakdown }}
            </p>
        </div>

        <div
            v-else-if="isSparkline"
            class="relative mt-4 flex flex-1 flex-col"
        >
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] uppercase"
                    :class="
                        hasTrendData
                            ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-700'
                            : 'border-slate-200 bg-slate-100/80 text-slate-500'
                    "
                >
                    {{ hasTrendData ? 'Trend context' : 'Pending trend context' }}
                </span>
            </div>

            <div class="mt-4 text-5xl font-semibold tracking-[-0.04em] text-slate-950">
                {{ formattedValue }}
            </div>

            <div :class="visualAreaClasses">
                <div
                    v-if="hasTrendData"
                    class="relative"
                >
                    <svg
                        viewBox="0 0 100 36"
                        class="h-16 w-full overflow-visible"
                        preserveAspectRatio="none"
                        aria-hidden="true"
                    >
                        <defs>
                            <linearGradient
                                :id="`${metricSvgIdBase}-sparkline-gradient`"
                                x1="0%"
                                y1="0%"
                                x2="100%"
                                y2="0%"
                            >
                                <stop offset="0%" stop-color="rgba(16,185,129,0.75)" />
                                <stop offset="100%" stop-color="rgba(14,165,233,0.95)" />
                            </linearGradient>
                            <filter :id="`${metricSvgIdBase}-sparkline-glow`">
                                <feGaussianBlur stdDeviation="1.4" result="blur" />
                                <feMerge>
                                    <feMergeNode in="blur" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                        </defs>
                        <polyline
                            :points="sparklinePoints"
                            fill="none"
                            :stroke="`url(#${metricSvgIdBase}-sparkline-gradient)`"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="3"
                            :filter="`url(#${metricSvgIdBase}-sparkline-glow)`"
                        />
                        <circle
                            v-if="sparklineLastPoint"
                            :cx="sparklineLastPoint.x"
                            :cy="sparklineLastPoint.y"
                            r="2.8"
                            fill="rgba(255,255,255,0.95)"
                            stroke="rgba(14,165,233,0.95)"
                            stroke-width="1.8"
                        />
                    </svg>
                </div>

                <div
                    v-else
                    class="flex h-full min-h-[4rem] flex-col justify-center rounded-[1rem] border border-dashed border-slate-200 bg-slate-50/70 px-4"
                >
                    <div class="relative h-10">
                        <div class="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-slate-200" />
                        <div class="absolute left-0 top-1/2 h-1.5 w-1.5 -translate-y-1/2 rounded-full bg-slate-300" />
                        <div class="absolute right-0 top-1/2 h-1.5 w-1.5 -translate-y-1/2 rounded-full bg-slate-300" />
                    </div>
                    <p class="mt-2 text-xs font-medium text-slate-500">
                        Pending trend context
                    </p>
                </div>
            </div>

            <p class="mt-4 max-w-[28ch] text-sm leading-6 text-slate-600">
                {{ breakdown }}
            </p>
        </div>

        <div
            v-else-if="isTrendLine"
            class="relative mt-4 flex flex-1 flex-col"
        >
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold tracking-[0.14em] uppercase"
                    :class="
                        hasTrendData
                            ? 'border-violet-200/80 bg-violet-50/90 text-violet-700'
                            : 'border-slate-200 bg-slate-100/80 text-slate-500'
                    "
                >
                    {{ hasTrendData ? 'Mapped trend view' : 'Trend pending' }}
                </span>
            </div>

            <div class="mt-4 text-5xl font-semibold tracking-[-0.04em] text-slate-950">
                {{ formattedValue }}
            </div>

            <div :class="visualAreaClasses">
                <div
                    v-if="hasTrendData"
                    class="relative overflow-hidden rounded-[1rem] border border-slate-100/80 bg-[linear-gradient(180deg,rgba(248,250,252,0.95),rgba(241,245,249,0.75))] px-3 py-4"
                >
                    <div class="pointer-events-none absolute inset-x-3 top-4 h-px bg-slate-200/70" />
                    <div class="pointer-events-none absolute inset-x-3 top-1/2 h-px -translate-y-1/2 bg-slate-200/50" />
                    <div class="pointer-events-none absolute inset-x-3 bottom-4 h-px bg-slate-200/70" />

                    <svg
                        viewBox="0 0 100 56"
                        class="relative h-28 w-full overflow-visible"
                        preserveAspectRatio="none"
                        aria-hidden="true"
                    >
                        <defs>
                            <linearGradient
                                :id="`${metricSvgIdBase}-trend-gradient`"
                                x1="0%"
                                y1="0%"
                                x2="100%"
                                y2="0%"
                            >
                                <stop offset="0%" stop-color="rgba(139,92,246,0.72)" />
                                <stop offset="100%" stop-color="rgba(14,165,233,0.96)" />
                            </linearGradient>
                            <filter :id="`${metricSvgIdBase}-trend-glow`">
                                <feGaussianBlur stdDeviation="1.8" result="blur" />
                                <feMerge>
                                    <feMergeNode in="blur" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                        </defs>
                        <polyline
                            :points="trendLinePoints"
                            fill="none"
                            :stroke="`url(#${metricSvgIdBase}-trend-gradient)`"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="3"
                            :filter="`url(#${metricSvgIdBase}-trend-glow)`"
                        />
                        <circle
                            v-if="trendLineLastPoint"
                            :cx="trendLineLastPoint.x"
                            :cy="trendLineLastPoint.y"
                            r="3"
                            fill="rgba(255,255,255,0.96)"
                            stroke="rgba(14,165,233,0.98)"
                            stroke-width="2"
                        />
                    </svg>
                </div>

                <div
                    v-else
                    class="flex min-h-[7rem] items-center justify-center rounded-[1rem] border border-dashed border-slate-200 bg-slate-50/70 px-4 py-5 text-center text-sm leading-6 text-slate-500"
                >
                    Trend data will appear when mapped.
                </div>
            </div>

            <p class="mt-4 max-w-[28ch] text-sm leading-6 text-slate-600">
                {{ breakdown }}
            </p>
        </div>

        <div
            v-else
            class="mt-4 text-3xl font-semibold tracking-tight text-slate-950"
        >
            {{ formattedValue }}
        </div>

        <div
            v-if="
                !isNumberCard &&
                !isProgressRateBar &&
                !isHorizontalBar &&
                !isComparisonBars &&
                !isSparkline &&
                !isTrendLine
            "
            :class="visualAreaClasses"
        >
            <div
                v-if="resolvedVisualType === 'number_card'"
                class="flex h-full items-center justify-between gap-3"
            >
                <div class="h-2 w-16 rounded-full bg-slate-200" />
                <div class="text-3xl font-semibold tracking-tight text-slate-300">
                    #
                </div>
            </div>

            <div
                v-else-if="resolvedVisualType === 'trend_line' || resolvedVisualType === 'sparkline'"
                class="flex h-full items-end gap-2"
            >
                <div class="h-4 w-6 rounded-t-lg bg-slate-200" />
                <div class="h-7 w-6 rounded-t-lg bg-slate-300" />
                <div class="h-10 w-6 rounded-t-lg bg-slate-200" />
                <div class="h-12 w-6 rounded-t-lg bg-slate-300" />
                <div class="h-16 w-6 rounded-t-lg bg-slate-200" />
            </div>

            <div
                v-else-if="
                    resolvedVisualType === 'horizontal_bar' ||
                    resolvedVisualType === 'comparison_bars' ||
                    resolvedVisualType === 'ranked_list' ||
                    resolvedVisualType === 'table'
                "
                class="space-y-3"
            >
                <div class="flex items-center gap-3">
                    <div class="h-2 w-16 rounded-full bg-slate-200" />
                    <div class="h-3 flex-1 rounded-full bg-slate-200" />
                </div>
                <div class="flex items-center gap-3">
                    <div class="h-2 w-12 rounded-full bg-slate-200" />
                    <div class="h-3 w-3/4 rounded-full bg-slate-300" />
                </div>
                <div class="flex items-center gap-3">
                    <div class="h-2 w-10 rounded-full bg-slate-200" />
                    <div class="h-3 w-1/2 rounded-full bg-slate-200" />
                </div>
            </div>

            <div
                v-else-if="resolvedVisualType === 'funnel_chart'"
                class="flex h-full flex-col items-center justify-center gap-2"
            >
                <div class="h-4 w-4/5 rounded-lg bg-slate-200" />
                <div class="h-4 w-3/5 rounded-lg bg-slate-300" />
                <div class="h-4 w-2/5 rounded-lg bg-slate-200" />
                <div class="h-4 w-1/4 rounded-lg bg-slate-300" />
            </div>

            <div
                v-else-if="
                    resolvedVisualType === 'progress_rate_bar' ||
                    resolvedVisualType === 'donut_chart'
                "
                class="flex h-full items-center justify-center gap-4"
            >
                <div class="h-16 w-16 rounded-full border-[10px] border-slate-200 border-t-slate-300" />
                <div class="space-y-2">
                    <div class="h-2 w-16 rounded-full bg-slate-200" />
                    <div class="h-2 w-12 rounded-full bg-slate-300" />
                    <div class="h-2 w-10 rounded-full bg-slate-200" />
                </div>
            </div>

            <div
                v-else-if="
                    resolvedVisualType === 'timeline_duration' ||
                    resolvedVisualType === 'flow_diagram'
                "
                class="flex h-full items-center gap-3"
            >
                <div class="flex h-full flex-col justify-between py-1">
                    <div class="h-2 w-2 rounded-full bg-slate-300" />
                    <div class="h-2 w-2 rounded-full bg-slate-200" />
                    <div class="h-2 w-2 rounded-full bg-slate-300" />
                </div>
                <div class="relative h-16 flex-1 overflow-hidden rounded-xl bg-slate-100">
                    <div class="absolute top-3 left-4 right-4 h-[2px] bg-slate-200" />
                    <div class="absolute top-8 left-8 right-8 h-[2px] bg-slate-300" />
                    <div class="absolute top-[2.8rem] left-10 h-3 w-3 rounded-full bg-slate-300" />
                </div>
            </div>

            <div
                v-else
                class="flex h-full items-center justify-between gap-3"
            >
                <div class="h-2 w-16 rounded-full bg-slate-200" />
                <div class="text-3xl font-semibold tracking-tight text-slate-300">
                    #
                </div>
            </div>
        </div>

        <div
            v-if="
                !isNumberCard &&
                !isProgressRateBar &&
                !isHorizontalBar &&
                !isComparisonBars &&
                !isSparkline &&
                !isTrendLine
            "
            class="mt-4 text-sm leading-6 text-slate-500"
        >
            {{ breakdown }}
        </div>
    </button>
</template>
