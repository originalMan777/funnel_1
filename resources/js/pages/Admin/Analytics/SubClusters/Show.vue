<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue';
import AnalyticsKpiCard from '@/components/admin/analytics/AnalyticsKpiCard.vue';
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue';
import SubClusterVisualCard from '@/components/admin/analytics/SubClusterVisualCard.vue';
import { formatNumber } from '@/components/admin/analytics/formatters';

type Metric = {
    key?: string;
    label: string;
    value: string | number;
    displayValue?: string | number | null;
    description?: string | null;
    helper?: string | null;
    insight?: string | null;
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
        summaryShort?: string;
        summaryFull?: string;
        flatHref?: string | null;
    };
    metricGroups: MetricGroup[];
}>();

const subClusterForCards = computed(() => ({
    key: props.subCluster.key,
    label: props.subCluster.label,
    description: props.subCluster.description,
    metricGroups: props.metricGroups,
}));

const totalMetrics = computed(() =>
    props.metricGroups.reduce((sum, group) => sum + group.metrics.length, 0),
);

const summaryCards = computed(() => [
    {
        label: 'Metric Groups',
        value: formatNumber(props.metricGroups.length),
    },
    {
        label: 'Metrics',
        value: formatNumber(totalMetrics.value),
        tone: 'sky' as const,
    },
    {
        label: 'Cluster',
        value: props.cluster.label,
    },
]);

const currentRoute = computed(() =>
    route('admin.analytics.sub-clusters.show', {
        clusterKey: props.cluster.key,
        subClusterKey: props.subCluster.key,
    }),
);
</script>

<template>
    <Head :title="`Analytics ${subCluster.label}`" />

    <AdminLayout>
        <AnalyticsShell>
            <template #header>
                <AnalyticsHeader
                    :title="subCluster.label"
                    :description="subCluster.description"
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
                            {{ cluster.label }} Cluster
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                            {{ subCluster.label }}
                        </h2>
                        <p class="mt-3 text-sm leading-6 font-medium text-slate-700">
                            {{ subCluster.summaryShort || subCluster.description }}
                        </p>
                        <p
                            v-if="subCluster.summaryFull"
                            class="mt-2 text-sm leading-6 text-slate-600"
                        >
                            {{ subCluster.summaryFull }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <Link
                                :href="
                                    route('admin.analytics.clusters.show', {
                                        clusterKey: cluster.key,
                                        from: filters.from,
                                        to: filters.to,
                                    })
                                "
                                class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
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
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p
                            class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                        >
                            Metric Groups
                        </p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-950">
                            Visual group cards
                        </h3>
                    </div>
                    <p class="text-sm text-slate-500">
                        2-3 metric previews per group
                    </p>
                </div>

                <div
                    v-if="metricGroups.length === 0"
                    class="mt-6 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-sm text-slate-500"
                >
                    No metric groups were resolved for this sub-cluster in the
                    selected range.
                </div>

                <div
                    v-else
                    class="mt-6 grid gap-5 xl:grid-cols-2"
                >
                    <SubClusterVisualCard
                        v-for="metricGroup in metricGroups"
                        :key="metricGroup.key"
                        :cluster="cluster"
                        :sub-cluster="subClusterForCards"
                        :metric-group="metricGroup"
                        mode="metric-group"
                    />
                </div>
            </section>
        </AnalyticsShell>
    </AdminLayout>
</template>
