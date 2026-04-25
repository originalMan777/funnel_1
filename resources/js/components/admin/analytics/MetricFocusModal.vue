<script setup lang="ts">
import { computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDuration } from '@/components/admin/analytics/formatters';
import { resolveMetricDefinition } from '@/components/admin/analytics/metricDefinitionRegistry';

type FocusMetric = {
    key?: string;
    label: string;
    value: string | number;
    displayValue?: string | number | null;
    definition?: string | null;
    description?: string | null;
    helper?: string | null;
    dataSource?: string | null;
    status?: 'good' | 'warning' | 'poor' | 'neutral' | string | null;
    statusLabel?: string | null;
    trendLabel?: string | null;
    delta?: string | number | null;
    insight?: string | null;
    recommendation?: string | null;
    formula?: string | null;
    whyItMatters?: string | null;
    affects?: string[];
    relatedMetrics?: Array<{
        label: string;
        value: string | number;
        helper?: string | null;
    }>;
};

type FocusContext = {
    key: string;
    label: string;
};

const props = defineProps<{
    metric: FocusMetric | null;
    open: boolean;
    cluster?: FocusContext | null;
    subCluster?: FocusContext | null;
    metricGroup?: FocusContext | null;
    availableMetrics?: FocusMetric[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const handleOpenChange = (nextOpen: boolean) => {
    if (!nextOpen) {
        emit('close');
    }
};

const formatMetricValue = (metric: FocusMetric | null) => {
    if (!metric) {
        return '—';
    }

    const value = String(metric.displayValue ?? metric.value);

    return metric.helper === 'seconds' && value !== '—'
        ? formatDuration(Number(value))
        : value;
};

const dataSourceLabel = computed(() => {
    if (props.metric?.dataSource === 'real') {
        return 'Real report value';
    }

    if (props.metric?.dataSource === 'local_demo') {
        return 'Local demo report value';
    }

    return 'No data source';
});

const statusLabel = computed(() => props.metric?.statusLabel ?? 'Baseline');

const statusBadgeClasses = computed(() => {
    const status = props.metric?.status ?? 'neutral';

    const classes: Record<string, string> = {
        good: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        warning: 'border-amber-200 bg-amber-50 text-amber-700',
        poor: 'border-rose-200 bg-rose-50 text-rose-700',
        neutral: 'border-slate-200 bg-slate-50 text-slate-600',
    };

    return classes[status] ?? classes.neutral;
});

const relatedMetrics = computed(() => {
    if (props.metric?.relatedMetrics?.length) {
        return props.metric.relatedMetrics.slice(0, 3);
    }

    if (!props.metric || !props.availableMetrics?.length) {
        return [];
    }

    return props.availableMetrics
        .filter((metric) => metric.label !== props.metric?.label)
        .slice(0, 3);
});

const resolvedMetricDefinition = computed(() =>
    resolveMetricDefinition(props.metric, {
        clusterKey: props.cluster?.key,
        clusterLabel: props.cluster?.label,
        subClusterKey: props.subCluster?.key,
        subClusterLabel: props.subCluster?.label,
        metricGroupKey: props.metricGroup?.key,
        metricGroupLabel: props.metricGroup?.label,
    }),
);

const resolvedDescription = computed(
    () =>
        props.metric?.definition ||
        resolvedMetricDefinition.value?.definition ||
        props.metric?.description ||
        'Definition is unavailable for this metric.',
);

const resolvedHeaderDescription = computed(
    () =>
        resolvedMetricDefinition.value?.meaning ||
        props.metric?.description ||
        'Metric definition metadata is unavailable for this metric.',
);

const resolvedFormula = computed(
    () =>
        props.metric?.formula ||
        resolvedMetricDefinition.value?.formula ||
        'Formula is unavailable for this metric.',
);

const resolvedWhyItMatters = computed(
    () =>
        props.metric?.whyItMatters ||
        resolvedMetricDefinition.value?.whyItMatters ||
        'Why this matters is unavailable for this metric.',
);

const resolvedAffects = computed(
    () =>
        resolvedMetricDefinition.value?.affects ||
        props.metric?.affects ||
        [],
);

const resolvedOperatorGuidance = computed(
    () =>
        props.metric?.recommendation ||
        resolvedMetricDefinition.value?.operatorGuidance ||
        'Operator guidance is not mapped for this metric yet.',
);
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent
            class="max-h-[92vh] overflow-y-auto border-slate-200 bg-white p-0 shadow-2xl sm:max-w-6xl"
        >
            <div class="space-y-8 p-6 sm:p-8">
                <DialogHeader class="space-y-3">
                    <p
                        class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                    >
                        Metric Focus
                    </p>
                    <DialogTitle class="text-3xl font-semibold text-slate-950">
                        {{ props.metric?.label ?? 'Metric' }}
                    </DialogTitle>
                    <DialogDescription class="max-w-3xl text-sm leading-6 text-slate-600">
                        {{ resolvedHeaderDescription }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                    <section
                        class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5"
                    >
                        <p
                            class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                        >
                            Current Value
                        </p>
                        <div class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">
                            {{ formatMetricValue(props.metric) }}
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-semibold tracking-[0.14em] uppercase"
                                :class="statusBadgeClasses"
                            >
                                {{ statusLabel }}
                            </span>
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase">
                                {{ dataSourceLabel }}
                            </span>
                            <span
                                v-if="props.metric?.trendLabel"
                                class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase"
                            >
                                {{ props.metric.trendLabel }}
                            </span>
                        </div>
                        <p
                            v-if="props.metric?.helper"
                            class="mt-2 text-sm font-medium text-slate-500"
                        >
                            Units: {{ props.metric.helper }}
                        </p>
                    </section>

                    <section
                        class="rounded-[1.5rem] border border-slate-200 bg-white p-5"
                    >
                        <p
                            class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                        >
                            Metric Context
                        </p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-xs font-medium text-slate-500">
                                    Cluster
                                </div>
                                <div class="mt-2 text-base font-semibold text-slate-950">
                                    {{ props.cluster?.label ?? '—' }}
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-xs font-medium text-slate-500">
                                    Sub-Cluster
                                </div>
                                <div class="mt-2 text-base font-semibold text-slate-950">
                                    {{ props.subCluster?.label ?? '—' }}
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-xs font-medium text-slate-500">
                                    Metric Group
                                </div>
                                <div class="mt-2 text-base font-semibold text-slate-950">
                                    {{ props.metricGroup?.label ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="grid gap-6 xl:grid-cols-[1.4fr,0.95fr]">
                    <section class="space-y-6 rounded-[1.5rem] border border-slate-200 bg-white p-5">
                        <section>
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Definition
                            </p>
                            <div
                                class="mt-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600"
                            >
                                {{ resolvedDescription }}
                            </div>
                        </section>

                        <section>
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Formula
                            </p>
                            <div
                                class="mt-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600"
                            >
                                {{ resolvedFormula }}
                            </div>
                        </section>

                        <section>
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Insight
                            </p>
                            <div
                                class="mt-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600"
                            >
                                {{ props.metric?.insight || resolvedWhyItMatters }}
                            </div>
                        </section>

                        <section>
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Recommendation
                            </p>
                            <div
                                class="mt-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600"
                            >
                                {{ props.metric?.recommendation || resolvedOperatorGuidance }}
                            </div>
                        </section>

                        <section>
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Visual Area
                            </p>
                            <div
                                class="mt-3 flex min-h-[18rem] items-center justify-center rounded-[1.25rem] border border-dashed border-slate-300 bg-slate-50 px-6 text-center text-sm leading-6 text-slate-500"
                            >
                                This metric’s chart or focused visual can be mounted
                                here when a real visualization is wired.
                            </div>
                        </section>
                    </section>

                    <section class="space-y-6">
                        <section
                            class="rounded-[1.5rem] border border-slate-200 bg-white p-5"
                        >
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                What Affects It
                            </p>
                            <div
                                v-if="resolvedAffects.length"
                                class="mt-4 flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="item in resolvedAffects"
                                    :key="item"
                                    class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-sm text-slate-600"
                                >
                                    {{ item }}
                                </span>
                            </div>
                            <div
                                v-else
                                class="mt-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600"
                            >
                                Drivers are not mapped for this metric yet.
                            </div>
                        </section>

                        <section
                            class="rounded-[1.5rem] border border-slate-200 bg-white p-5"
                        >
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Related Metrics
                            </p>
                            <div
                                v-if="relatedMetrics.length > 0"
                                class="mt-4 space-y-3"
                            >
                                <div
                                    v-for="metric in relatedMetrics"
                                    :key="metric.label"
                                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                                >
                                    <div class="text-xs font-medium text-slate-500">
                                        {{ metric.label }}
                                    </div>
                                    <div class="mt-2 text-lg font-semibold text-slate-950">
                                        {{ formatMetricValue(metric) }}
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else
                                class="mt-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600"
                            >
                                Related metrics are unavailable for this metric.
                            </div>
                        </section>

                        <section
                            class="rounded-[1.5rem] border border-slate-200 bg-white p-5"
                        >
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Operator Action Guidance
                            </p>
                            <div
                                class="mt-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-6 text-slate-600"
                            >
                                {{ resolvedOperatorGuidance }}
                            </div>
                        </section>
                    </section>
                </div>

                <DialogFooter
                    class="border-t border-slate-200 px-0 pt-6 sm:justify-between"
                >
                    <p class="text-sm leading-6 text-slate-500">
                        Metrics stay in context here so operators can review
                        definitions without leaving the metric group page.
                    </p>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        @click="emit('close')"
                    >
                        Close
                    </button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
