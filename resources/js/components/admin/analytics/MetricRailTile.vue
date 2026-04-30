<script setup lang="ts">
import { computed } from 'vue';
import { formatDuration } from '@/components/admin/analytics/formatters';
import {
    getApprovedVisualDefinition,
    resolveApprovedVisual,
    type ApprovedAnalyticsVisualKey,
    type MetricVisualContext,
} from '@/components/admin/analytics/visualizationRegistry';

type RailMetric = {
    key?: string;
    label: string;
    value: string | number;
    displayValue?: string | number | null;
    helper?: string | null;
    status?: 'good' | 'warning' | 'poor' | 'neutral' | string | null;
    statusLabel?: string | null;
    trendLabel?: string | null;
    delta?: string | number | null;
    approvedVisualKey?: ApprovedAnalyticsVisualKey | null;
};

const props = defineProps<{
    metric: RailMetric;
    visualContext: MetricVisualContext;
}>();

const emit = defineEmits<{
    select: [metric: RailMetric];
}>();

const formattedValue = computed(() => {
    const value = String(props.metric.displayValue ?? props.metric.value ?? '-');

    return props.metric.helper === 'seconds' && value !== '-'
        ? formatDuration(Number(value))
        : value;
});

const approvedVisual = computed(() =>
    props.metric.approvedVisualKey
        ? getApprovedVisualDefinition(props.metric.approvedVisualKey)
        : resolveApprovedVisual({
        ...props.visualContext,
        metricKey: props.visualContext.metricKey ?? props.metric.key,
        metricLabel: props.visualContext.metricLabel ?? props.metric.label,
    }),
);

const parseNumericValue = (value: string | number | null | undefined) => {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : null;
    }

    const normalized = String(value ?? '')
        .replace(/,/g, '')
        .replace('%', '')
        .trim();

    if (!normalized || normalized === '-' || normalized === '—') {
        return null;
    }

    const parsed = Number(normalized);

    return Number.isFinite(parsed) ? parsed : null;
};

const parsePercentValue = (value: string | number | null | undefined) => {
    const parsed = parseNumericValue(value);

    if (parsed === null) {
        return null;
    }

    if (String(value ?? '').includes('%')) {
        return parsed;
    }

    if (parsed >= 0 && parsed <= 1) {
        return parsed * 100;
    }

    return parsed >= 0 && parsed <= 100 ? parsed : null;
};

const numericValue = computed(() =>
    parseNumericValue(props.metric.displayValue ?? props.metric.value),
);
const percentValue = computed(() =>
    parsePercentValue(props.metric.displayValue ?? props.metric.value),
);
const visualFill = computed(() => {
    if (percentValue.value !== null) {
        return Math.min(Math.max(percentValue.value, 0), 100);
    }

    if (numericValue.value === null) {
        return 42;
    }

    if (numericValue.value >= 0 && numericValue.value <= 100) {
        return Math.min(Math.max(numericValue.value, 0), 100);
    }

    return Math.min(Math.max(Math.log10(numericValue.value + 1) * 22, 14), 94);
});

const strokeDasharray = computed(() =>
    `${Number((visualFill.value * 1.57).toFixed(1))} 157`,
);

const donutDasharray = computed(() =>
    `${Number((visualFill.value * 3.02).toFixed(1))} 302`,
);

const statusLabel = computed(
    () => props.metric.trendLabel ?? props.metric.statusLabel ?? 'Baseline',
);

const statusClasses = computed(() => {
    const status = props.metric.status ?? 'neutral';
    const classes: Record<string, string> = {
        good: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        warning: 'border-amber-200 bg-amber-50 text-amber-700',
        poor: 'border-rose-200 bg-rose-50 text-rose-700',
        neutral: 'border-slate-200 bg-white text-slate-500',
    };

    return classes[status] ?? classes.neutral;
});
</script>

<template>
    <button
        type="button"
        class="group grid h-36 w-64 shrink-0 grid-rows-[auto,1fr,auto] rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm shadow-slate-200/60 transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-xl hover:shadow-sky-100/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300"
        @click="emit('select', metric)"
    >
        <div class="flex items-start justify-between gap-3">
            <p class="line-clamp-2 text-sm font-semibold leading-5 text-slate-800">
                {{ metric.label }}
            </p>
            <span
                class="shrink-0 rounded-full border px-2 py-1 text-[10px] font-semibold uppercase"
                :class="statusClasses"
            >
                {{ statusLabel }}
            </span>
        </div>

        <div class="mt-3 flex items-end justify-between gap-3">
            <div>
                <div class="text-2xl font-semibold tracking-tight text-slate-950">
                    {{ formattedValue }}
                </div>
                <div
                    v-if="metric.delta"
                    class="mt-1 text-xs font-medium text-slate-500"
                >
                    {{ metric.delta }}
                </div>
            </div>

            <div
                class="flex h-12 w-16 shrink-0 items-center justify-center rounded-xl border border-slate-100 bg-slate-50 px-2 py-2"
                aria-hidden="true"
            >
                <svg
                    v-if="approvedVisual?.key === 'half-ring-score'"
                    viewBox="0 0 80 52"
                    class="h-11 w-14"
                >
                    <path d="M 10 42 A 30 30 0 0 1 70 42" fill="none" stroke="rgba(203,213,225,0.95)" stroke-width="8" stroke-linecap="round" />
                    <path d="M 10 42 A 30 30 0 0 1 70 42" fill="none" stroke="rgba(14,165,233,0.95)" stroke-width="8" stroke-linecap="round" :stroke-dasharray="strokeDasharray" />
                </svg>

                <svg
                    v-else-if="approvedVisual?.key === 'half-ring-score-v2'"
                    viewBox="0 0 80 52"
                    class="h-11 w-14"
                >
                    <path d="M 8 42 A 32 32 0 0 1 72 42" fill="none" stroke="rgba(203,213,225,0.95)" stroke-width="10" stroke-linecap="round" />
                    <path d="M 8 42 A 32 32 0 0 1 72 42" fill="none" stroke="rgba(15,23,42,0.95)" stroke-width="10" stroke-linecap="round" :stroke-dasharray="strokeDasharray" />
                    <circle cx="40" cy="12" r="4" fill="white" stroke="rgba(15,23,42,0.95)" stroke-width="3" />
                </svg>

                <svg
                    v-else-if="approvedVisual?.key === 'premium-donut'"
                    viewBox="0 0 64 64"
                    class="h-11 w-11"
                >
                    <circle cx="32" cy="32" r="20" fill="none" stroke="rgba(203,213,225,0.95)" stroke-width="9" />
                    <circle cx="32" cy="32" r="20" fill="none" stroke="rgba(14,165,233,0.95)" stroke-width="9" stroke-linecap="round" :stroke-dasharray="donutDasharray" transform="rotate(-90 32 32)" />
                </svg>

                <div
                    v-else-if="approvedVisual?.key === 'funnel-flow'"
                    class="w-full space-y-1"
                >
                    <div class="mx-auto h-1.5 w-full rounded bg-sky-300" />
                    <div class="mx-auto h-1.5 w-5/6 rounded bg-sky-400" />
                    <div class="mx-auto h-1.5 w-3/5 rounded bg-sky-500" />
                    <div class="mx-auto h-1.5 w-2/5 rounded bg-sky-700" />
                </div>

                <div
                    v-else-if="approvedVisual?.key === 'range-variance'"
                    class="relative h-2 w-full rounded-full bg-slate-200"
                >
                    <div class="absolute inset-y-0 left-[12%] right-[18%] rounded-full bg-gradient-to-r from-amber-300 via-sky-300 to-emerald-300" />
                    <div class="absolute top-1/2 h-4 w-4 -translate-y-1/2 rounded-full border-2 border-white bg-slate-950" :style="{ left: `calc(${Math.min(Math.max(visualFill, 10), 90)}% - 0.5rem)` }" />
                </div>

                <div
                    v-else-if="approvedVisual?.key === 'premium-vertical-bar'"
                    class="flex h-full w-full items-end justify-center gap-1"
                >
                    <span class="h-[38%] flex-1 rounded-t bg-slate-200" />
                    <span class="flex-1 rounded-t bg-sky-500" :style="{ height: `${Math.min(Math.max(visualFill, 14), 94)}%` }" />
                    <span class="h-[54%] flex-1 rounded-t bg-slate-300" />
                </div>

                <div
                    v-else-if="approvedVisual?.key === 'stacked-performance'"
                    class="h-3 w-full overflow-hidden rounded-full bg-slate-200"
                >
                    <span class="inline-block h-full bg-sky-400" :style="{ width: `${Math.min(Math.max(visualFill, 18), 64)}%` }" />
                    <span class="inline-block h-full bg-emerald-300" style="width: 22%" />
                    <span class="inline-block h-full bg-amber-300" style="width: 14%" />
                </div>

                <div
                    v-else-if="approvedVisual?.key === 'source-ranking-table'"
                    class="w-full space-y-1.5"
                >
                    <div class="h-1.5 w-full rounded-full bg-sky-400" />
                    <div class="h-1.5 w-3/4 rounded-full bg-slate-300" />
                    <div class="h-1.5 w-1/2 rounded-full bg-emerald-300" />
                </div>

                <div
                    v-else
                    class="w-full space-y-1.5"
                >
                    <div class="h-2 w-full rounded-full bg-sky-400" />
                    <div class="h-2 w-2/3 rounded-full bg-slate-300" />
                </div>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
            <span class="truncate text-xs font-medium text-slate-500">
                {{ visualContext.metricGroupLabel }}
            </span>
            <span
                v-if="approvedVisual"
                class="shrink-0 text-[11px] font-semibold text-sky-700"
            >
                {{ approvedVisual.cardNumber }} {{ approvedVisual.label }}
            </span>
        </div>
    </button>
</template>
