<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import MetricCard from '@/components/admin/analytics/MetricCard.vue';
import {
    getApprovedVisualDefinition,
    resolveApprovedVisual,
    type ApprovedAnalyticsVisualKey,
} from '@/components/admin/analytics/visualizationRegistry';

type CommandMetric = {
    key?: string;
    label: string;
    value: string | number;
    displayValue?: string | number | null;
    helper?: string | null;
    status?: string | null;
    statusLabel?: string | null;
    trendLabel?: string | null;
    approvedVisualKey?: ApprovedAnalyticsVisualKey | null;
};

type CommandMetricGroup = {
    key: string;
    label: string;
    description?: string | null;
    detailHref?: string | null;
    groupReport?: {
        summary: string;
    } | null;
    metrics: CommandMetric[];
};

type CommandSubCluster = {
    key: string;
    label: string;
    description: string;
    href?: string | null;
    flatHref?: string | null;
    metricGroups: CommandMetricGroup[];
};

type CommandCluster = {
    key: string;
    label: string;
    description: string;
    href: string;
    clusterReport?: {
        summary: string;
    } | null;
    subClusters: CommandSubCluster[];
};

const props = defineProps<{
    cluster: CommandCluster;
    featuredGroup: CommandMetricGroup | null;
    featuredMetricKeys: string[];
}>();

const featuredMetrics = computed(() => {
    const metrics = props.featuredGroup?.metrics ?? [];

    return props.featuredMetricKeys
        .map((metricKey) => metrics.find((metric) => metric.key === metricKey))
        .filter((metric): metric is CommandMetric => metric !== undefined);
});

const sourceSubCluster = computed(() =>
    props.cluster.subClusters.find((subCluster) =>
        subCluster.metricGroups.some(
            (metricGroup) => metricGroup.key === props.featuredGroup?.key,
        ),
    ) ?? null,
);

const approvedVisualForMetric = (metric: CommandMetric) =>
    metric.approvedVisualKey
        ? getApprovedVisualDefinition(metric.approvedVisualKey)
        : resolveApprovedVisual({
            clusterKey: props.cluster.key,
            clusterLabel: props.cluster.label,
            subClusterKey: sourceSubCluster.value?.key,
            subClusterLabel: sourceSubCluster.value?.label,
            metricGroupKey: props.featuredGroup?.key,
            metricGroupLabel: props.featuredGroup?.label,
            metricKey: metric.key,
            metricLabel: metric.label,
        });
</script>

<template>
    <article
        class="flex min-h-[33rem] flex-col overflow-hidden rounded-[1.75rem] border border-slate-200 bg-[linear-gradient(145deg,rgba(255,255,255,0.98),rgba(248,250,252,0.95)_54%,rgba(240,249,255,0.88))] p-6 shadow-[0_24px_70px_-44px_rgba(15,23,42,0.55)]"
    >
        <div class="flex items-start justify-between gap-5">
            <div>
                <p class="text-[11px] font-semibold tracking-[0.2em] text-slate-500 uppercase">
                    Analytics Cluster
                </p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                    {{ cluster.label }}
                </h2>
            </div>
            <Link
                :href="cluster.href"
                class="shrink-0 rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
            >
                Open Cluster
            </Link>
        </div>

        <p class="mt-5 text-base leading-7 text-slate-600">
            {{ cluster.clusterReport?.summary || cluster.description }}
        </p>

        <section class="mt-6 rounded-2xl border border-white/80 bg-white/80 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75)]">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase">
                        Featured Group
                    </p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-950">
                        {{ featuredGroup?.label ?? 'Configured Metrics' }}
                    </h3>
                </div>
                <Link
                    v-if="featuredGroup?.detailHref"
                    :href="featuredGroup.detailHref"
                    class="text-sm font-semibold text-sky-700 transition hover:text-sky-800"
                >
                    Open group
                </Link>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div
                    v-for="metric in featuredMetrics"
                    :key="`${featuredGroup?.key}-${metric.key}`"
                    class="min-h-56"
                >
                    <MetricCard
                        class="h-full"
                        :metric="metric"
                        variant="compact"
                        :approved-visual-type="approvedVisualForMetric(metric)?.key"
                        :visual-context="{
                            clusterKey: cluster.key,
                            clusterLabel: cluster.label,
                            subClusterKey: sourceSubCluster?.key,
                            subClusterLabel: sourceSubCluster?.label,
                            metricGroupKey: featuredGroup?.key,
                            metricGroupLabel: featuredGroup?.label,
                            metricKey: metric.key,
                            metricLabel: metric.label,
                        }"
                    />
                </div>
            </div>
        </section>

        <div class="mt-6 grid gap-3">
            <section
                v-for="subCluster in cluster.subClusters"
                :key="subCluster.key"
                class="rounded-2xl border border-slate-200 bg-white/75 p-4"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-950">
                            {{ subCluster.label }}
                        </h3>
                        <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-500">
                            {{ subCluster.description }}
                        </p>
                    </div>
                    <Link
                        v-if="subCluster.href"
                        :href="subCluster.href"
                        class="shrink-0 text-sm font-semibold text-sky-700 transition hover:text-sky-800"
                    >
                        Open
                    </Link>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="metricGroup in subCluster.metricGroups"
                        :key="metricGroup.key"
                        :href="metricGroup.detailHref ?? subCluster.href ?? cluster.href"
                        class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                    >
                        {{ metricGroup.label }}
                    </Link>
                </div>
            </section>
        </div>
    </article>
</template>
