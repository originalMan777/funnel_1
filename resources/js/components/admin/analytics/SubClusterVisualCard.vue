<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import MetricCard from '@/components/admin/analytics/MetricCard.vue';
import { formatNumber } from '@/components/admin/analytics/formatters';
import { resolveApprovedVisual } from '@/components/admin/analytics/visualizationRegistry';

type VisualMetric = {
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
};

type VisualMetricGroup = {
    key: string;
    label: string;
    description?: string | null;
    detailHref?: string | null;
    groupReport?: {
        summary: string;
    } | null;
    metrics: VisualMetric[];
};

type VisualSubCluster = {
    key: string;
    label: string;
    description: string;
    href?: string | null;
    metricGroups?: VisualMetricGroup[];
};

const props = withDefaults(
    defineProps<{
        cluster: {
            key: string;
            label: string;
        };
        subCluster: VisualSubCluster;
        metricGroup?: VisualMetricGroup | null;
        mode?: 'subcluster' | 'metric-group';
    }>(),
    {
        mode: 'subcluster',
        metricGroup: null,
    },
);

const emit = defineEmits<{
    selectMetric: [payload: {
        metric: VisualMetric;
        subCluster: VisualSubCluster;
        metricGroup: VisualMetricGroup;
    }];
}>();

const sourceMetricGroup = computed(
    () => props.metricGroup ?? props.subCluster.metricGroups?.[0] ?? null,
);

const previewMetrics = computed(() =>
    (sourceMetricGroup.value?.metrics ?? []).slice(0, 3),
);

const href = computed(() =>
    props.mode === 'metric-group'
        ? sourceMetricGroup.value?.detailHref
        : props.subCluster.href,
);

const title = computed(() =>
    props.mode === 'metric-group'
        ? sourceMetricGroup.value?.label ?? 'Metric group'
        : props.subCluster.label,
);

const description = computed(() =>
    props.mode === 'metric-group'
        ? sourceMetricGroup.value?.description || props.subCluster.description
        : props.subCluster.description,
);

const reportLine = computed(
    () =>
        sourceMetricGroup.value?.groupReport?.summary ||
        previewMetrics.value.find((metric) => metric.insight)?.insight ||
        null,
);

const countLabel = computed(() =>
    props.mode === 'metric-group'
        ? `${formatNumber(previewMetrics.value.length)} preview metrics`
        : `${formatNumber(props.subCluster.metricGroups?.length ?? 0)} metric group${
              (props.subCluster.metricGroups?.length ?? 0) === 1 ? '' : 's'
          }`,
);

const approvedVisualForMetric = (metric: VisualMetric) =>
    resolveApprovedVisual({
        clusterKey: props.cluster.key,
        clusterLabel: props.cluster.label,
        subClusterKey: props.subCluster.key,
        subClusterLabel: props.subCluster.label,
        metricGroupKey: sourceMetricGroup.value?.key,
        metricGroupLabel: sourceMetricGroup.value?.label,
        metricKey: metric.key,
        metricLabel: metric.label,
    });
</script>

<template>
    <article
        class="group flex h-full flex-col overflow-hidden rounded-[1.5rem] border border-slate-200 bg-[linear-gradient(145deg,rgba(255,255,255,0.98),rgba(248,250,252,0.96)_58%,rgba(240,249,255,0.9))] p-5 shadow-[0_22px_55px_-36px_rgba(15,23,42,0.48)] transition hover:border-sky-200 hover:shadow-[0_28px_65px_-40px_rgba(14,165,233,0.46)]"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase">
                    {{ mode === 'metric-group' ? 'Metric Group' : 'Sub-Cluster' }}
                </p>
                <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
                    {{ title }}
                </h3>
            </div>
            <span class="shrink-0 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700">
                {{ countLabel }}
            </span>
        </div>

        <p class="mt-4 text-base leading-7 text-slate-600">
            {{ description }}
        </p>

        <div
            v-if="reportLine"
            class="mt-4 rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 text-base leading-7 text-slate-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.72)]"
        >
            {{ reportLine }}
        </div>

        <div
            v-if="previewMetrics.length > 0"
            class="mt-5 grid gap-3 lg:grid-cols-3"
            :class="previewMetrics.length === 1 ? 'lg:grid-cols-1' : ''"
        >
            <MetricCard
                v-for="metric in previewMetrics"
                :key="`${sourceMetricGroup?.key}-${metric.label}`"
                :metric="metric"
                variant="compact"
                :approved-visual-type="approvedVisualForMetric(metric)?.key"
                :visual-context="{
                    clusterKey: cluster.key,
                    clusterLabel: cluster.label,
                    subClusterKey: subCluster.key,
                    subClusterLabel: subCluster.label,
                    metricGroupKey: sourceMetricGroup?.key,
                    metricGroupLabel: sourceMetricGroup?.label,
                    metricKey: metric.key,
                    metricLabel: metric.label,
                }"
                clickable
                @select="
                    sourceMetricGroup &&
                        emit('selectMetric', {
                            metric,
                            subCluster,
                            metricGroup: sourceMetricGroup,
                        })
                "
            />
        </div>

        <div class="mt-auto flex items-center justify-between gap-4 border-t border-slate-200 pt-5">
            <span class="text-sm font-semibold text-slate-600">
                {{ sourceMetricGroup?.label ?? 'Configured metrics' }}
            </span>
            <Link
                v-if="href"
                :href="href"
                class="text-sm font-semibold text-sky-700 transition group-hover:text-sky-800"
            >
                {{ mode === 'metric-group' ? 'Open details' : 'Open sub-cluster' }}
            </Link>
        </div>
    </article>
</template>
