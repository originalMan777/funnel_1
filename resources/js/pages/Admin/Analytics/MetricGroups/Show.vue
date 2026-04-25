<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue';
import AnalyticsKpiCard from '@/components/admin/analytics/AnalyticsKpiCard.vue';
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue';
import MetricCard from '@/components/admin/analytics/MetricCard.vue';
import MetricFocusModal from '@/components/admin/analytics/MetricFocusModal.vue';
import { formatNumber } from '@/components/admin/analytics/formatters';
import { resolveApprovedVisual } from '@/components/admin/analytics/visualizationRegistry';

type Metric = {
    key?: string;
    label: string;
    value: string | number;
    description?: string | null;
    helper?: string | null;
    formula?: string | null;
    whyItMatters?: string | null;
    affects?: string[];
    relatedMetrics?: Array<{
        label: string;
        value: string | number;
        helper?: string | null;
    }>;
};

const props = defineProps<{
    filters: {
        from: string;
        to: string;
        presets: Array<{ label: string; days: number }>;
    };
    cluster: {
        key: string;
        label: string;
        description: string;
    };
    subCluster: {
        key: string;
        label: string;
        description: string;
        flatHref?: string | null;
    };
    metricGroup: {
        key: string;
        label: string;
        description?: string | null;
        groupReport?: {
            summary: string;
        };
    };
    metrics: Metric[];
}>();

const selectedMetric = ref<Metric | null>(null);
const metricModalOpen = ref(false);

const openMetric = (metric: Metric) => {
    selectedMetric.value = metric;
    metricModalOpen.value = true;
};

const closeMetric = () => {
    metricModalOpen.value = false;
};

const currentRoute = computed(() =>
    route('admin.analytics.metric-groups.show', {
        clusterKey: props.cluster.key,
        subClusterKey: props.subCluster.key,
        metricGroupKey: props.metricGroup.key,
    }),
);

const summaryCards = computed(() => [
    {
        label: 'Metrics',
        value: formatNumber(props.metrics.length),
    },
    {
        label: 'Sub-Cluster',
        value: props.subCluster.label,
        tone: 'sky' as const,
    },
    {
        label: 'Cluster',
        value: props.cluster.label,
    },
]);

const approvedVisualForMetric = (metric: Metric) =>
    resolveApprovedVisual({
        clusterKey: props.cluster.key,
        clusterLabel: props.cluster.label,
        subClusterKey: props.subCluster.key,
        subClusterLabel: props.subCluster.label,
        metricGroupKey: props.metricGroup.key,
        metricGroupLabel: props.metricGroup.label,
        metricKey: metric.key,
        metricLabel: metric.label,
    });

</script>

<template>
    <Head :title="`Analytics ${metricGroup.label}`" />

    <AdminLayout>
        <AnalyticsShell>
            <template #header>
                <AnalyticsHeader
                    :title="metricGroup.label"
                    :description="metricGroup.description || subCluster.description"
                    :filters="filters"
                    :current-route="currentRoute"
                />
            </template>

            <section
                class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/50"
            >
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div class="max-w-3xl">
                        <p
                            class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                        >
                            {{ cluster.label }} / {{ subCluster.label }}
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                            {{ metricGroup.label }}
                        </h2>
                        <p class="mt-3 text-sm leading-6 font-medium text-slate-700">
                            {{
                                metricGroup.description ||
                                'This metric group is resolved from the current analytics report data for the selected date range.'
                            }}
                        </p>
                        <div
                            v-if="metricGroup.groupReport"
                            class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700"
                        >
                            {{ metricGroup.groupReport.summary }}
                        </div>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <Link
                                :href="
                                    route('admin.analytics.sub-clusters.show', {
                                        clusterKey: cluster.key,
                                        subClusterKey: subCluster.key,
                                        from: filters.from,
                                        to: filters.to,
                                    })
                                "
                                class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Back to sub-cluster
                            </Link>
                            <Link
                                :href="
                                    route('admin.analytics.clusters.show', {
                                        clusterKey: cluster.key,
                                        from: filters.from,
                                        to: filters.to,
                                    })
                                "
                                class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-50"
                            >
                                Back to cluster
                            </Link>
                            <Link
                                v-if="subCluster.flatHref"
                                :href="subCluster.flatHref"
                                class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-50"
                            >
                                Compatibility report
                            </Link>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[24rem]">
                        <AnalyticsKpiCard
                            v-for="card in summaryCards"
                            :key="card.label"
                            :label="card.label"
                            :value="card.value"
                            :tone="card.tone"
                        />
                    </div>
                </div>
            </section>

            <section
                class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/50"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                        >
                            Metrics
                        </p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-950">
                            Focus metrics
                        </h3>
                    </div>
                    <p class="text-sm text-slate-500">
                        Click a metric to open the focus modal
                    </p>
                </div>

                <div
                    v-if="metrics.length === 0"
                    class="mt-6 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-sm text-slate-500"
                >
                    No metrics were resolved for this metric group in the
                    selected range.
                </div>

                <div
                    v-else
                    class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                >
                    <MetricCard
                        v-for="metric in metrics"
                        :key="`${metricGroup.key}-${metric.label}`"
                        :metric="metric"
                        :variant="approvedVisualForMetric(metric)?.cardVariant"
                        :approved-visual-type="approvedVisualForMetric(metric)?.key"
                        :visual-context="{
                            clusterKey: cluster.key,
                            clusterLabel: cluster.label,
                            subClusterKey: subCluster.key,
                            subClusterLabel: subCluster.label,
                            metricGroupKey: metricGroup.key,
                            metricGroupLabel: metricGroup.label,
                            metricKey: metric.key,
                            metricLabel: metric.label,
                        }"
                        clickable
                        @select="openMetric"
                    />
                </div>
            </section>

            <MetricFocusModal
                :metric="selectedMetric"
                :open="metricModalOpen"
                :cluster="cluster"
                :sub-cluster="subCluster"
                :metric-group="metricGroup"
                :available-metrics="metrics"
                @close="closeMetric"
            />
        </AnalyticsShell>
    </AdminLayout>
</template>
