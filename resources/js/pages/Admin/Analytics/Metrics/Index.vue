<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue';
import MetricCard from '@/components/admin/analytics/MetricCard.vue';
import MetricFocusModal from '@/components/admin/analytics/MetricFocusModal.vue';
import {
    type AnalyticsClusterKey,
    type AnalyticsSubClusterKey,
} from '@/components/admin/analytics/analyticsHierarchyRegistry';
import {
    availableTestingVisuals,
    resolveApprovedVisual,
    type ApprovedVisualCategory,
    type ApprovedVisualComplexity,
    type ApprovedAnalyticsVisualKey,
} from '@/components/admin/analytics/visualizationRegistry';

type Filters = {
    from: string;
    to: string;
    presets: Array<{ label: string; days: number }>;
};

type FocusMetric = {
    occurrenceKey?: string;
    key?: string;
    label: string;
    value: string | number;
    displayValue?: string | number | null;
    description?: string | null;
    helper?: string | null;
    dataSource?: string | null;
    status?: 'good' | 'warning' | 'poor' | 'neutral' | string | null;
    statusLabel?: string | null;
    trendLabel?: string | null;
    delta?: string | number | null;
    insight?: string | null;
    recommendation?: string | null;
    definition?: string | null;
    formula?: string | null;
    whyItMatters?: string | null;
    approvedVisualKey?: ApprovedAnalyticsVisualKey | null;
    affects?: string[];
    cluster?: {
        key: AnalyticsClusterKey;
        label: string;
    } | null;
    subCluster?: {
        key: AnalyticsSubClusterKey;
        label: string;
    } | null;
    metricGroup?: {
        key: string;
        label: string;
        detailHref?: string | null;
    } | null;
    relatedMetrics?: Array<{
        label: string;
        value: string | number;
        helper?: string | null;
    }>;
};

type MetricCatalogItem = {
    id: string;
    key: string;
    label: string;
    cluster: {
        key: AnalyticsClusterKey;
        label: string;
    };
    subCluster: {
        key: AnalyticsSubClusterKey;
        label: string;
    };
    metricGroup: {
        key: string;
        label: string;
        detailHref?: string | null;
    } | null;
    description: string;
    previewPurpose: string[];
    approvedVisual: {
        key: ApprovedAnalyticsVisualKey;
        label: string;
        cardNumber: string;
        category: ApprovedVisualCategory;
        complexity: ApprovedVisualComplexity;
    } | null;
    modalMetric: FocusMetric;
};

const props = defineProps<{
    filters: Filters;
    analyticsClusters: unknown[];
    metricOccurrences: FocusMetric[];
}>();

const search = ref('');
const selectedVisualType = ref('all');
const selectedStatus = ref('all');
const selectedCluster = ref('all');
const selectedMetricGroup = ref('all');
const selectedMetricId = ref<string | null>(null);
const metricModalOpen = ref(false);

const catalogMetrics = computed<MetricCatalogItem[]>(() =>
    props.metricOccurrences.map((metric) => {
        const approvedVisual = resolveApprovedVisual({
            clusterKey: metric.cluster?.key,
            clusterLabel: metric.cluster?.label,
            subClusterKey: metric.subCluster?.key,
            subClusterLabel: metric.subCluster?.label,
            metricGroupKey: metric.metricGroup?.key,
            metricGroupLabel: metric.metricGroup?.label,
            metricKey: metric.key,
            metricLabel: metric.label,
        });

        return {
            id:
                metric.occurrenceKey ??
                `${metric.cluster?.key}-${metric.subCluster?.key}-${metric.metricGroup?.key}-${metric.key}-${metric.label}`,
            key: metric.key ?? metric.label,
            label: metric.label,
            cluster: metric.cluster as MetricCatalogItem['cluster'],
            subCluster: metric.subCluster as MetricCatalogItem['subCluster'],
            metricGroup: metric.metricGroup ?? null,
            description:
                metric.definition ||
                metric.description ||
                'Definition not configured yet.',
            previewPurpose: approvedVisual ? [approvedVisual.key.replace(/-/g, ' ')] : [],
            approvedVisual: approvedVisual
                ? {
                    key: approvedVisual.key,
                    label: approvedVisual.label,
                    cardNumber: approvedVisual.cardNumber,
                    category: approvedVisual.category,
                    complexity: approvedVisual.complexity,
                }
                : null,
            modalMetric: metric,
        };
    }),
);

const filteredMetrics = computed(() => {
    const term = search.value.trim().toLowerCase();

    return catalogMetrics.value.filter((metric) => {
        const status = metric.approvedVisual ? 'Approved' : 'Needs Review';
        const matchesSearch =
            !term ||
            [
            metric.label,
            metric.key,
            metric.cluster.label,
            metric.subCluster.label,
            metric.metricGroup?.label ?? '',
            metric.description,
            metric.approvedVisual?.label ?? '',
            metric.approvedVisual?.cardNumber ?? '',
            metric.approvedVisual?.category ?? '',
            metric.approvedVisual?.complexity ?? '',
            metric.previewPurpose.join(' '),
            ]
                .join(' ')
                .toLowerCase()
                .includes(term);

        return (
            matchesSearch &&
            (selectedVisualType.value === 'all' ||
                metric.approvedVisual?.category === selectedVisualType.value) &&
            (selectedStatus.value === 'all' || status === selectedStatus.value) &&
            (selectedCluster.value === 'all' ||
                metric.cluster.key === selectedCluster.value) &&
            (selectedMetricGroup.value === 'all' ||
                metric.metricGroup?.key === selectedMetricGroup.value)
        );
    });
});

const selectedMetric = computed(
    () =>
        catalogMetrics.value.find((metric) => metric.id === selectedMetricId.value) ??
        null,
);

const openMetric = (metric: MetricCatalogItem) => {
    selectedMetricId.value = metric.id;
    metricModalOpen.value = true;
};

const closeMetric = () => {
    metricModalOpen.value = false;
};

const catalogSummary = computed(() => ({
    total: catalogMetrics.value.length,
    visible: filteredMetrics.value.length,
    visuals: availableTestingVisuals.length,
}));

const visualTypeOptions = computed(() =>
    Array.from(new Set(availableTestingVisuals.map((visual) => visual.category))).sort(),
);

const clusterOptions = computed(() =>
    Array.from(
        new Map(
            catalogMetrics.value.map((metric) => [
                metric.cluster.key,
                metric.cluster.label,
            ]),
        ),
    ).map(([key, label]) => ({ key, label })),
);

const metricGroupOptions = computed(() =>
    Array.from(
        new Map(
            catalogMetrics.value
                .filter((metric) => metric.metricGroup)
                .map((metric) => [
                    metric.metricGroup?.key ?? '',
                    metric.metricGroup?.label ?? '',
                ]),
        ),
    )
        .filter(([key]) => key)
        .map(([key, label]) => ({ key, label })),
);

</script>

<template>
    <Head title="Visual Approval Catalog" />

    <AdminLayout>
        <AnalyticsShell>
            <template #main>
                <div class="space-y-8">
                    <section class="rounded-[1rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase">
                                    Analytics Visuals
                                </p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
                                    Visual Approval Catalog
                                </h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Every metric and its visualization system. Approve, test, and compare visuals.
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-[0.85rem] border border-slate-200 bg-slate-50 px-5 py-4">
                                    <div class="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">
                                        Total Metrics
                                    </div>
                                    <div class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                                        {{ catalogSummary.total }}
                                    </div>
                                </div>
                                <div class="rounded-[0.85rem] border border-slate-200 bg-slate-50 px-5 py-4">
                                    <div class="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">
                                        Visible Now
                                    </div>
                                    <div class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                                        {{ catalogSummary.visible }}
                                    </div>
                                </div>
                                <div class="rounded-[0.85rem] border border-slate-200 bg-slate-50 px-5 py-4">
                                    <div class="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">
                                        Registry Visuals
                                    </div>
                                    <div class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                                        {{ catalogSummary.visuals }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 xl:grid-cols-[1.4fr,repeat(4,minmax(0,1fr))]">
                            <label for="analytics-metric-search" class="sr-only">Search metrics</label>
                            <input
                                id="analytics-metric-search"
                                v-model="search"
                                type="search"
                                placeholder="Search metric, key, cluster, group, definition, or visual"
                                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                            />

                            <select
                                v-model="selectedVisualType"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                            >
                                <option value="all">Visual Type</option>
                                <option
                                    v-for="type in visualTypeOptions"
                                    :key="type"
                                    :value="type"
                                >
                                    {{ type }}
                                </option>
                            </select>

                            <select
                                v-model="selectedStatus"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                            >
                                <option value="all">Status</option>
                                <option value="Approved">Approved</option>
                                <option value="Testing">Testing</option>
                                <option value="Needs Review">Needs Review</option>
                            </select>

                            <select
                                v-model="selectedCluster"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                            >
                                <option value="all">Cluster</option>
                                <option
                                    v-for="cluster in clusterOptions"
                                    :key="cluster.key"
                                    :value="cluster.key"
                                >
                                    {{ cluster.label }}
                                </option>
                            </select>

                            <select
                                v-model="selectedMetricGroup"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                            >
                                <option value="all">Metric Group</option>
                                <option
                                    v-for="group in metricGroupOptions"
                                    :key="group.key"
                                    :value="group.key"
                                >
                                    {{ group.label }}
                                </option>
                            </select>
                        </div>
                    </section>

                    <section
                        v-if="filteredMetrics.length === 0"
                        class="rounded-[1rem] border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm leading-6 text-slate-500 shadow-sm"
                    >
                        No metrics match the current filters.
                    </section>

                    <section
                        v-else
                        class="grid gap-5 sm:grid-cols-2 2xl:grid-cols-3"
                    >
                        <MetricCard
                            v-for="metric in filteredMetrics"
                            :key="metric.id"
                            :metric="metric.modalMetric"
                            :approved-visual-type="metric.approvedVisual?.key"
                            :visual-context="{
                                clusterKey: metric.cluster.key,
                                clusterLabel: metric.cluster.label,
                                subClusterKey: metric.subCluster.key,
                                subClusterLabel: metric.subCluster.label,
                                metricGroupKey: metric.metricGroup?.key,
                                metricGroupLabel: metric.metricGroup?.label,
                                metricKey: metric.key,
                                metricLabel: metric.label,
                            }"
                            approval-mode
                            @select="openMetric(metric)"
                        />
                    </section>
                </div>
            </template>
        </AnalyticsShell>

        <MetricFocusModal
            :metric="selectedMetric?.modalMetric ?? null"
            :open="metricModalOpen"
            :cluster="selectedMetric?.cluster ?? null"
            :sub-cluster="selectedMetric?.subCluster ?? null"
            :metric-group="selectedMetric?.metricGroup ?? null"
            :available-metrics="
                filteredMetrics
                    .filter((metric) => metric.subCluster.key === selectedMetric?.subCluster.key)
                    .map((metric) => metric.modalMetric)
            "
            @close="closeMetric"
        />
    </AdminLayout>
</template>
