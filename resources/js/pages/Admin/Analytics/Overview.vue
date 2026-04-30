<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue';
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue';
import ClusterCommandCard from '@/components/admin/analytics/ClusterCommandCard.vue';
import MetricFocusModal from '@/components/admin/analytics/MetricFocusModal.vue';
import MetricRailTile from '@/components/admin/analytics/MetricRailTile.vue';

type Metric = {
    occurrenceKey?: string;
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
    cluster?: Context | null;
    subCluster?: Context | null;
    metricGroup?: (Context & { detailHref?: string | null }) | null;
    relatedMetrics?: Array<{
        label: string;
        value: string | number;
        helper?: string | null;
    }>;
};

type Context = {
    key: string;
    label: string;
};

type MetricGroup = {
    key: string;
    label: string;
    description?: string | null;
    detailHref?: string | null;
    groupReport?: {
        summary: string;
    } | null;
    metrics: Metric[];
};

type SubCluster = {
    key: string;
    label: string;
    description: string;
    href?: string | null;
    flatHref?: string | null;
    metricGroups: MetricGroup[];
};

type Cluster = {
    key: string;
    label: string;
    description: string;
    summaryShort: string;
    summaryFull: string;
    href: string;
    clusterReport?: {
        summary: string;
    } | null;
    subClusters: SubCluster[];
};

const props = defineProps<{
    filters: {
        from: string;
        to: string;
        presets: Array<{ label: string; days: number }>;
    };
    overviewReport: {
        paragraphs: string[];
    };
    analyticsClusters: Cluster[];
    metricOccurrences: Metric[];
}>();

const selectedMetric = ref<Metric | null>(null);
const metricModalOpen = ref(false);

const clusterOrder = ['traffic', 'capture', 'flow', 'behavior', 'results', 'source'];

const featuredGroups: Record<string, { groupKey: string; metricKeys: string[] }> = {
    traffic: {
        groupKey: 'cta_performance',
        metricKeys: ['views', 'clicks', 'ctr'],
    },
    capture: {
        groupKey: 'lead_box_lifecycle',
        metricKeys: ['views', 'submissions', 'failures'],
    },
    flow: {
        groupKey: 'funnel_performance',
        metricKeys: ['completion_rate', 'drop_off', 'duration'],
    },
    behavior: {
        groupKey: 'scenario_performance',
        metricKeys: ['views', 'conversion_rate', 'duration'],
    },
    results: {
        groupKey: 'conversion_performance',
        metricKeys: ['submissions', 'time_to_conversion'],
    },
    source: {
        groupKey: 'attribution_performance',
        metricKeys: ['submissions', 'attribution_coverage'],
    },
};

const orderedClusters = computed(() =>
    clusterOrder
        .map((clusterKey) =>
            props.analyticsClusters.find((cluster) => cluster.key === clusterKey),
        )
        .filter((cluster): cluster is Cluster => cluster !== undefined),
);

const rangeLabel = computed(() => `${props.filters.from} to ${props.filters.to}`);

const metricContext = (metric: Metric) => ({
    clusterKey: metric.cluster?.key,
    clusterLabel: metric.cluster?.label,
    subClusterKey: metric.subCluster?.key,
    subClusterLabel: metric.subCluster?.label,
    metricGroupKey: metric.metricGroup?.key,
    metricGroupLabel: metric.metricGroup?.label,
    metricKey: metric.key,
    metricLabel: metric.label,
});

const featuredGroupFor = (cluster: Cluster) => {
    const featuredGroup = featuredGroups[cluster.key];

    if (!featuredGroup) {
        return null;
    }

    return (
        cluster.subClusters
            .flatMap((subCluster) => subCluster.metricGroups)
            .find((metricGroup) => metricGroup.key === featuredGroup.groupKey) ?? null
    );
};

const openMetric = (metric: Metric) => {
    selectedMetric.value = metric;
    metricModalOpen.value = true;
};

const closeMetric = () => {
    metricModalOpen.value = false;
};
</script>

<template>
    <Head title="Analytics Overview" />

    <AdminLayout>
        <AnalyticsShell>
            <template #header>
                <AnalyticsHeader
                    title="Overview"
                    description="Analytics command center for system health, metric scanning, and cluster-level understanding."
                    :filters="filters"
                    :current-route="route('admin.analytics.index')"
                />
            </template>

            <section
                class="overflow-hidden rounded-[2rem] border border-slate-200 bg-[linear-gradient(145deg,rgba(255,255,255,0.98),rgba(248,250,252,0.95)_48%,rgba(240,249,255,0.86))] p-6 shadow-[0_24px_70px_-48px_rgba(15,23,42,0.55)] sm:p-8"
            >
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold tracking-[0.22em] text-slate-500 uppercase">
                            System Health Report
                        </p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                            Analytics command center
                        </h1>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700">
                            {{ rangeLabel }}
                        </span>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600">
                            {{ metricOccurrences.length }} metric signals
                        </span>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 text-base leading-7 text-slate-700 lg:grid-cols-2">
                    <p
                        v-for="paragraph in overviewReport.paragraphs"
                        :key="paragraph"
                        class="rounded-2xl border border-white/80 bg-white/75 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.75)]"
                    >
                        {{ paragraph }}
                    </p>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold tracking-[0.2em] text-slate-500 uppercase">
                            Speed Layer
                        </p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-950">
                            Metric rail
                        </h2>
                    </div>
                    <p class="text-sm font-medium text-slate-500">
                        Click any metric for focus details
                    </p>
                </div>

                <div
                    class="-mx-4 overflow-x-auto px-4 pb-3 [scrollbar-width:thin] sm:-mx-6 sm:px-6"
                >
                    <div class="flex w-max gap-3">
                        <MetricRailTile
                            v-for="metric in metricOccurrences"
                            :key="metric.occurrenceKey ?? `${metric.metricGroup?.key}-${metric.key}`"
                            :metric="metric"
                            :visual-context="metricContext(metric)"
                            @select="openMetric"
                        />
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <p class="text-[11px] font-semibold tracking-[0.2em] text-slate-500 uppercase">
                        Understanding Layer
                    </p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-950">
                        Cluster command cards
                    </h2>
                </div>

                <div class="grid gap-6 xl:grid-cols-2">
                    <ClusterCommandCard
                        v-for="cluster in orderedClusters"
                        :key="cluster.key"
                        :cluster="cluster"
                        :featured-group="featuredGroupFor(cluster)"
                        :featured-metric-keys="featuredGroups[cluster.key]?.metricKeys ?? []"
                    />
                </div>
            </section>

            <MetricFocusModal
                :metric="selectedMetric"
                :open="metricModalOpen"
                :cluster="selectedMetric?.cluster ?? null"
                :sub-cluster="selectedMetric?.subCluster ?? null"
                :metric-group="selectedMetric?.metricGroup ?? null"
                :available-metrics="metricOccurrences"
                @close="closeMetric"
            />
        </AnalyticsShell>
    </AdminLayout>
</template>
