<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue';
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue';
import DonutPreview from '@/components/admin/analytics/DonutPreview.vue';
import MetricFocusModal from '@/components/admin/analytics/MetricFocusModal.vue';
import {
    getAnalyticsClusters,
    getMetricsForSubCluster,
    type AnalyticsClusterKey,
    type AnalyticsSubClusterKey,
} from '@/components/admin/analytics/analyticsHierarchyRegistry';
import { resolveMetricDefinition } from '@/components/admin/analytics/metricDefinitionRegistry';
import {
    getVisualConfigForMetric,
    getVisualizationDefinition,
    resolveApprovedVisual,
    type AnalyticsVisualType,
} from '@/components/admin/analytics/visualizationRegistry';

type Filters = {
    from: string;
    to: string;
    presets: Array<{ label: string; days: number }>;
};

type FocusMetric = {
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
    } | null;
    description: string;
    previewLabel: string | null;
    previewType: AnalyticsVisualType | null;
    previewStatus: string | null;
    previewPurpose: string[];
    approvedVisual: {
        key: string;
        label: string;
        cardNumber: string;
    } | null;
    modalMetric: FocusMetric;
};

const props = defineProps<{
    filters: Filters;
}>();

const search = ref('');
const selectedMetricId = ref<string | null>(null);
const metricModalOpen = ref(false);

const analyticsClusters = computed(() => getAnalyticsClusters());

const catalogMetrics = computed<MetricCatalogItem[]>(() =>
    analyticsClusters.value.flatMap((cluster) =>
        cluster.subClusters.flatMap((subCluster) => {
            const subClusterMetrics = getMetricsForSubCluster(subCluster);
            const relatedMetrics = subClusterMetrics.map((metric) => ({
                key: metric.key,
                label: metric.label,
            }));

            return subCluster.metricGroups.flatMap((metricGroupDefinition) =>
                metricGroupDefinition.metrics.map((metric) => {
                    const definition = resolveMetricDefinition(metric, {
                        clusterKey: cluster.key,
                        clusterLabel: cluster.label,
                        subClusterKey: subCluster.key,
                        subClusterLabel: subCluster.label,
                        metricGroupKey: metricGroupDefinition.key,
                        metricGroupLabel: metricGroupDefinition.label,
                    });
                    const visualConfig = getVisualConfigForMetric(metric);
                    const previewDefinition = visualConfig?.primaryVisual
                        ? getVisualizationDefinition(visualConfig.primaryVisual)
                        : null;
                    const approvedVisual = resolveApprovedVisual({
                        clusterKey: cluster.key,
                        clusterLabel: cluster.label,
                        subClusterKey: subCluster.key,
                        subClusterLabel: subCluster.label,
                        metricGroupKey: metricGroupDefinition.key,
                        metricGroupLabel: metricGroupDefinition.label,
                        metricKey: metric.key,
                        metricLabel: metric.label,
                    });
                    const metricGroup = {
                        key: metricGroupDefinition.key,
                        label: metricGroupDefinition.label,
                    };

                    return {
                        id: `${cluster.key}-${subCluster.key}-${metricGroupDefinition.key}-${metric.key}-${metric.label}`,
                        key: metric.key,
                        label: metric.label,
                        cluster: {
                            key: cluster.key,
                            label: cluster.label,
                        },
                        subCluster: {
                            key: subCluster.key,
                            label: subCluster.label,
                        },
                        metricGroup,
                        description:
                            definition?.definition ||
                            metric.description ||
                            'Definition not configured yet.',
                        previewLabel: previewDefinition?.label ?? null,
                        previewType: previewDefinition?.type ?? null,
                        previewStatus: previewDefinition?.status ?? null,
                        previewPurpose: previewDefinition?.purpose ?? [],
                        approvedVisual: approvedVisual
                            ? {
                                key: approvedVisual.key,
                                label: approvedVisual.label,
                                cardNumber: approvedVisual.cardNumber,
                            }
                            : null,
                        modalMetric: {
                            key: metric.key,
                            label: metric.label,
                            value: '—',
                            description:
                                definition?.definition ||
                                metric.description ||
                                'Definition not configured yet.',
                            formula: definition?.formula,
                            whyItMatters: definition?.whyItMatters,
                            affects: definition?.affects,
                            relatedMetrics: relatedMetrics
                                .filter(
                                    (relatedMetric) =>
                                        relatedMetric.label !== metric.label,
                                )
                                .slice(0, 3)
                                .map((relatedMetric) => ({
                                    label: relatedMetric.label,
                                    value: '—',
                                })),
                        },
                    };
                }),
            );
        }),
    ),
);

const filteredMetrics = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return catalogMetrics.value;
    }

    return catalogMetrics.value.filter((metric) =>
        [
            metric.label,
            metric.key,
            metric.cluster.label,
            metric.subCluster.label,
            metric.metricGroup?.label ?? '',
            metric.description,
            metric.previewLabel ?? '',
            metric.approvedVisual?.label ?? '',
            metric.approvedVisual?.cardNumber ?? '',
            metric.previewPurpose.join(' '),
        ]
            .join(' ')
            .toLowerCase()
            .includes(term),
    );
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

const currentRoute = computed(() => route('admin.analytics.metrics.index'));

const catalogSummary = computed(() => ({
    total: catalogMetrics.value.length,
    visible: filteredMetrics.value.length,
}));
</script>

<template>
    <Head title="Analytics Metrics Index" />

    <AdminLayout>
        <AnalyticsShell>
            <template #main>
                <div class="space-y-8">
                    <AnalyticsHeader
                        title="Metrics Index"
                        description="Catalog every analytics metric currently defined in the system so you can inspect the registry surface area without relying on report values."
                        :filters="filters"
                        :current-route="currentRoute"
                    />

                    <section
                        class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(160deg,rgba(255,255,255,0.98),rgba(248,250,252,0.96)_50%,rgba(240,249,255,0.92))] p-6 shadow-[0_24px_60px_-38px_rgba(15,23,42,0.35)]"
                    >
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase">
                                    Visibility Catalog
                                </p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
                                    Every registered analytics metric
                                </h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Each card below is derived from the existing hierarchy, definition, and visualization registries. No metric values are invented on this page.
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-[1.35rem] border border-white/80 bg-white/80 px-5 py-4 shadow-sm shadow-slate-200/70">
                                    <div class="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">
                                        Total Metrics
                                    </div>
                                    <div class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                                        {{ catalogSummary.total }}
                                    </div>
                                </div>
                                <div class="rounded-[1.35rem] border border-white/80 bg-white/80 px-5 py-4 shadow-sm shadow-slate-200/70">
                                    <div class="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">
                                        Visible Now
                                    </div>
                                    <div class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                                        {{ catalogSummary.visible }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label for="analytics-metric-search" class="sr-only">Search metrics</label>
                            <div class="relative">
                                <input
                                    id="analytics-metric-search"
                                    v-model="search"
                                    type="search"
                                    placeholder="Search by metric, key, cluster, sub-cluster, group, definition, or visual type"
                                    class="w-full rounded-[1.35rem] border border-slate-200 bg-white/90 px-4 py-3.5 text-sm text-slate-700 shadow-inner shadow-slate-100 outline-none transition focus:border-sky-300 focus:ring-2 focus:ring-sky-100"
                                />
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="filteredMetrics.length === 0"
                        class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white/75 px-6 py-12 text-center text-sm leading-6 text-slate-500 shadow-sm shadow-slate-200/60"
                    >
                        No metrics match the current search.
                    </section>

                    <section
                        v-else
                        class="grid gap-5 sm:grid-cols-2 2xl:grid-cols-3"
                    >
                        <button
                            v-for="metric in filteredMetrics"
                            :key="metric.id"
                            type="button"
                            class="group flex h-full min-h-[25rem] flex-col overflow-hidden rounded-[1.7rem] border border-slate-200/80 bg-[linear-gradient(155deg,rgba(255,255,255,0.98),rgba(248,250,252,0.95)_48%,rgba(241,245,249,0.98))] p-5 text-left shadow-[0_20px_48px_-36px_rgba(15,23,42,0.45)] transition duration-200 hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-[0_26px_60px_-34px_rgba(14,165,233,0.35)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300/70"
                            @click="openMetric(metric)"
                        >
                            <div class="absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.16),transparent_62%)]" />

                            <div class="relative flex h-full flex-col">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase">
                                            Metric
                                        </p>
                                        <h3 class="mt-2 text-xl font-semibold tracking-tight text-slate-950">
                                            {{ metric.label }}
                                        </h3>
                                        <p class="mt-2 text-xs font-medium tracking-[0.12em] text-slate-500 uppercase">
                                            {{ metric.key }}
                                        </p>
                                    </div>

                                    <span class="inline-flex items-center rounded-full border border-white/80 bg-white/90 px-3 py-1 text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase shadow-sm">
                                        View
                                    </span>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700">
                                        {{ metric.cluster.label }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                                        {{ metric.subCluster.label }}
                                    </span>
                                    <span
                                        v-if="metric.metricGroup"
                                        class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700"
                                    >
                                        {{ metric.metricGroup.label }}
                                    </span>
                                </div>

                                <p class="mt-5 line-clamp-4 text-sm leading-6 text-slate-600">
                                    {{ metric.description }}
                                </p>

                                <div class="mt-5 rounded-[1.35rem] border border-white/80 bg-white/85 p-4 shadow-inner shadow-slate-100/80">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">
                                                Visual Preview
                                            </p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                                {{
                                                    metric.approvedVisual
                                                        ? `${metric.approvedVisual.cardNumber} ${metric.approvedVisual.label}`
                                                        : (metric.previewLabel ?? 'No visual preview configured.')
                                                }}
                                            </p>
                                        </div>
                                        <span
                                            v-if="metric.previewStatus"
                                            class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-500 capitalize"
                                        >
                                            {{ metric.previewStatus }}
                                        </span>
                                    </div>

                                    <div
                                        v-if="metric.approvedVisual"
                                        class="mt-4 flex min-h-[7.5rem] items-center justify-center rounded-[1.1rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(248,250,252,0.88),rgba(255,255,255,0.98))] px-4"
                                    >
                                        <div class="w-full space-y-3">
                                            <div class="flex items-center justify-between text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">
                                                <span>{{ metric.approvedVisual.key.replace(/-/g, ' ') }}</span>
                                                <span>Approved</span>
                                            </div>
                                            <div
                                                class="h-4 overflow-hidden rounded-full bg-slate-200"
                                            >
                                                <div class="h-full w-2/3 rounded-full bg-gradient-to-r from-sky-400 to-emerald-400" />
                                            </div>
                                            <div class="grid grid-cols-3 gap-2">
                                                <div class="h-12 rounded-xl bg-slate-100" />
                                                <div class="h-12 rounded-xl bg-sky-100" />
                                                <div class="h-12 rounded-xl bg-slate-100" />
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-else-if="metric.previewType"
                                        class="mt-4 flex min-h-[7.5rem] items-center justify-center rounded-[1.1rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(248,250,252,0.88),rgba(255,255,255,0.98))] px-4"
                                    >
                                        <div class="w-full">
                                            <div
                                                v-if="metric.previewType === 'number_card'"
                                                class="space-y-3"
                                            >
                                                <div class="h-3 w-20 rounded-full bg-slate-200" />
                                                <div class="h-8 w-28 rounded-2xl bg-slate-900/85" />
                                                <div class="h-2.5 w-32 rounded-full bg-sky-100" />
                                            </div>

                                            <div
                                                v-else-if="metric.previewType === 'progress_rate_bar'"
                                                class="space-y-3"
                                            >
                                                <div class="h-3 w-24 rounded-full bg-slate-200" />
                                                <div class="h-4 overflow-hidden rounded-full bg-slate-200">
                                                    <div class="h-full w-2/3 rounded-full bg-gradient-to-r from-sky-400 to-emerald-400" />
                                                </div>
                                                <div class="h-2.5 w-28 rounded-full bg-slate-200" />
                                            </div>

                                            <div
                                                v-else-if="metric.previewType === 'comparison_bars' || metric.previewType === 'horizontal_bar' || metric.previewType === 'ranked_list' || metric.previewType === 'table'"
                                                class="space-y-2.5"
                                            >
                                                <div class="h-3 w-full rounded-full bg-slate-200">
                                                    <div class="h-full w-4/5 rounded-full bg-sky-400" />
                                                </div>
                                                <div class="h-3 w-full rounded-full bg-slate-200">
                                                    <div class="h-full w-3/5 rounded-full bg-slate-400" />
                                                </div>
                                                <div class="h-3 w-full rounded-full bg-slate-200">
                                                    <div class="h-full w-2/5 rounded-full bg-emerald-400" />
                                                </div>
                                            </div>

                                            <div
                                                v-else-if="metric.previewType === 'trend_line' || metric.previewType === 'sparkline'"
                                                class="relative h-20 overflow-hidden rounded-[1rem] bg-slate-50"
                                            >
                                                <svg viewBox="0 0 120 56" class="h-full w-full text-sky-500">
                                                    <path
                                                        d="M6 42 C24 30, 34 34, 48 20 S76 16, 88 24 S104 22, 114 12"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="4"
                                                    />
                                                </svg>
                                            </div>

                                            <div
                                                v-else-if="metric.previewType === 'timeline_duration'"
                                                class="space-y-4"
                                            >
                                                <div class="flex items-center justify-between text-[11px] font-medium text-slate-500">
                                                    <span>Start</span>
                                                    <span>End</span>
                                                </div>
                                                <div class="relative h-2 rounded-full bg-slate-200">
                                                    <div class="absolute inset-y-0 left-[12%] right-[18%] rounded-full bg-gradient-to-r from-amber-300 to-rose-300" />
                                                    <div class="absolute left-[12%] top-1/2 h-4 w-4 -translate-y-1/2 rounded-full border-2 border-white bg-amber-400 shadow-sm" />
                                                    <div class="absolute right-[18%] top-1/2 h-4 w-4 -translate-y-1/2 rounded-full border-2 border-white bg-rose-400 shadow-sm" />
                                                </div>
                                            </div>

                                            <div
                                                v-else-if="metric.previewType === 'funnel_chart'"
                                                class="space-y-2"
                                            >
                                                <div class="mx-auto h-4 w-full max-w-[14rem] rounded-md bg-sky-300/90" />
                                                <div class="mx-auto h-4 w-5/6 rounded-md bg-sky-400/80" />
                                                <div class="mx-auto h-4 w-3/5 rounded-md bg-sky-500/75" />
                                                <div class="mx-auto h-4 w-2/5 rounded-md bg-sky-600/70" />
                                            </div>

                                            <div
                                                v-else-if="metric.previewType === 'donut_chart'"
                                                class="py-1"
                                            >
                                                <DonutPreview
                                                    :metric="{
                                                        key: metric.key,
                                                        label: metric.label,
                                                    }"
                                                />
                                            </div>

                                            <div
                                                v-else
                                                class="flex min-h-[6rem] items-center justify-center rounded-[1rem] border border-dashed border-slate-300 bg-slate-50 px-4 text-center text-sm text-slate-500"
                                            >
                                                No visual preview configured.
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="mt-4 flex min-h-[7.5rem] items-center justify-center rounded-[1.1rem] border border-dashed border-slate-300 bg-slate-50 px-4 text-center text-sm text-slate-500"
                                    >
                                        No visual preview configured.
                                    </div>
                                </div>

                                <div class="mt-auto pt-5">
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="purpose in metric.previewPurpose.slice(0, 3)"
                                            :key="purpose"
                                            class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-500 capitalize"
                                        >
                                            {{ purpose.replace(/_/g, ' ') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </button>
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
