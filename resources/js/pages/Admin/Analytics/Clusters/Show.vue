<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue';
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue';
import SubClusterVisualCard from '@/components/admin/analytics/SubClusterVisualCard.vue';

type ClusterMetric = {
    key?: string;
    label: string;
    value: string | number;
    displayValue?: string | number | null;
    description?: string | null;
    helper?: string | null;
    insight?: string | null;
};

type ClusterMetricGroup = {
    key: string;
    label: string;
    description?: string | null;
    detailHref?: string | null;
    groupReport?: {
        summary: string;
    } | null;
    metrics: ClusterMetric[];
};

type ClusterSubCluster = {
    key: string;
    label: string;
    description: string;
    href: string;
    flatHref?: string | null;
    metricGroups: ClusterMetricGroup[];
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
        summaryShort: string;
        summaryFull: string;
    };
    clusterReport: {
        summary: string;
    };
    subClusters: ClusterSubCluster[];
}>();

const currentRoute = computed(() =>
    route('admin.analytics.clusters.show', {
        clusterKey: props.cluster.key,
    }),
);
</script>

<template>
    <Head :title="`Analytics ${cluster.label} Cluster`" />

    <AdminLayout>
        <AnalyticsShell>
            <template #header>
                <AnalyticsHeader
                    :title="cluster.label"
                    :description="cluster.description"
                    :filters="filters"
                    :current-route="currentRoute"
                    :show-hero="false"
                    :show-date-controls="false"
                />
            </template>

            <section
                class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/50 sm:p-6"
            >
                <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p
                            class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                        >
                            {{ cluster.label }} Subclusters
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
                            Choose the next drilldown
                        </h2>
                    </div>
                    <p class="max-w-xl text-sm leading-6 text-slate-500">
                        {{ cluster.summaryShort || clusterReport.summary }}
                    </p>
                </div>

                <div
                    v-if="subClusters.length === 0"
                    class="mt-6 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-sm leading-6 text-slate-500"
                >
                    No sub-clusters are configured for this analytics cluster yet.
                </div>

                <div
                    v-else
                    class="mt-6 grid gap-5 xl:grid-cols-2"
                >
                    <SubClusterVisualCard
                        v-for="subCluster in subClusters"
                        :key="subCluster.key"
                        :cluster="cluster"
                        :sub-cluster="subCluster"
                    />
                </div>
            </section>
        </AnalyticsShell>
    </AdminLayout>
</template>
