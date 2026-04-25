<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import AnalyticsBarComparison from '@/components/admin/analytics/AnalyticsBarComparison.vue';
import AnalyticsFunnelView from '@/components/admin/analytics/AnalyticsFunnelView.vue';
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue';
import AnalyticsMiniVisual from '@/components/admin/analytics/AnalyticsMiniVisual.vue';
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue';
import AnalyticsTrendChart from '@/components/admin/analytics/AnalyticsTrendChart.vue';
import {
    getAnalyticsClusters,
    type AnalyticsClusterKey,
    type AnalyticsSubClusterDefinition,
} from '@/components/admin/analytics/analyticsHierarchyRegistry';
import {
    formatDuration,
    formatNumber,
    formatPercent,
} from '@/components/admin/analytics/formatters';
import type {
    OverviewCategoryTile,
    OverviewHeroMetric,
    OverviewMetricKey,
    QuickViewPayload,
} from '@/components/admin/analytics/metricRegistry';
import { buildOverviewAnalyticsMap } from '@/components/admin/analytics/metricRegistry';
import MetricCard from '@/components/admin/analytics/MetricCard.vue';
import RecentlyViewedCard from '@/components/admin/analytics/RecentlyViewedCard.vue';
import { useQBarState } from '@/layouts/canonical/useQBarState';

type OverviewTrendRow = {
    date: string;
    page_views: number;
    cta_clicks: number;
    lead_form_submissions: number;
    popup_submissions: number;
    conversions: number;
};

type ViewportSelection =
    | { kind: 'cluster'; key: AnalyticsClusterKey }
    | { kind: 'metric'; key: OverviewMetricKey };

type ClusterCard = {
    key: AnalyticsClusterKey;
    title: string;
    description: string;
    summaryShort: string;
    summaryFull: string;
    href: string;
    metricLine: string;
    primaryMetric:
        | OverviewCategoryTile['metrics'][number]
        | null;
    secondaryMetric:
        | OverviewCategoryTile['metrics'][number]
        | null;
    subClusters: AnalyticsSubClusterDefinition[];
    previewMetrics: QuickViewPayload[];
    links: OverviewCategoryTile['links'];
};

const props = defineProps<{
    filters: {
        from: string;
        to: string;
        presets: Array<{ label: string; days: number }>;
    };
    readiness: {
        enabled: boolean;
        tables_ready: boolean;
        ingest_route: string;
        session_inactivity_timeout_minutes: number;
        bootstrap_ready: boolean;
    };
    summary: {
        visitors: number;
        sessions: number;
        event_types: number;
        events: number;
        attribution_touches: number;
        conversions: number;
        conversion_attributions: number;
        daily_rollups: number;
    };
    overview: {
        range: {
            from: string;
            to: string;
        };
        ready: boolean;
        summary_cards: {
            page_views: number;
            cta_clicks: number;
            lead_form_submissions: number;
            popup_submissions: number;
            conversions: number;
            average_session_duration_seconds: number | null;
            median_session_duration_seconds: number | null;
            average_time_to_conversion_seconds: number | null;
            median_time_to_conversion_seconds: number | null;
        };
        trend: OverviewTrendRow[];
        conversion_types: Array<{
            conversion_type_id: number;
            label: string;
            total: number;
        }>;
        top_scenarios: Array<{
            scenario_key: string;
            label: string;
            description: string | null;
            sessions: number;
            converted_sessions: number;
            conversion_total: number;
            conversion_rate: number | null;
            average_events: number;
            average_session_duration_seconds: number | null;
            median_session_duration_seconds: number | null;
        }>;
        top_funnels: Array<{
            key: string;
            label: string;
            description: string;
            conversion_count: number;
            average_elapsed_seconds: number | null;
            dismissed_without_submit?: number;
            step_timings?: Array<{
                key: string;
                label: string;
                average_elapsed_seconds: number | null;
            }>;
            special_timings?: Array<{
                key: string;
                label: string;
                average_elapsed_seconds: number | null;
            }>;
            top_drop_off?: {
                label: string;
                count: number;
                drop_off_to_next: number;
            } | null;
            steps: Array<{
                key: string;
                label: string;
                count: number;
                drop_off_to_next: number;
            }>;
        }>;
        attribution: {
            overview: {
                attributed_conversions: number;
                unattributed_conversions: number;
            };
        };
        interpretations: Array<{
            key: string;
            title: string;
            detail: string;
            evidence: Record<string, string | number | null>;
        }>;
    };
    overviewReport: {
        paragraphs: string[];
    };
}>();

const selectedMetricKey = ref<OverviewMetricKey>('conversions');
const selectedViewportItem = ref<ViewportSelection>({
    kind: 'cluster',
    key: 'traffic',
});

const {
    activateAnalyticsMode,
    deactivateAnalyticsMode,
    selectedAnalyticsMetric,
    setAnalyticsMetric,
} = useQBarState();

const percent = (numerator: number, denominator: number) => {
    if (denominator <= 0) {
        return null;
    }

    return (numerator / denominator) * 100;
};

const overviewRangeLabel = computed(
    () => `${props.overview.range.from} to ${props.overview.range.to}`,
);
const currentOverviewHref = computed(
    () =>
        `${route('admin.analytics.index')}?from=${props.filters.from}&to=${props.filters.to}`,
);
const topScenario = computed(() => props.overview.top_scenarios[0] ?? null);
const topFunnel = computed(() => props.overview.top_funnels[0] ?? null);
const topDropOff = computed(() => topFunnel.value?.top_drop_off ?? null);

const funnelCompletionRate = computed(() => {
    if (!topFunnel.value?.steps.length) {
        return null;
    }

    return percent(
        topFunnel.value.conversion_count,
        topFunnel.value.steps[0].count,
    );
});

const categoryLinks = computed(() => ({
    funnels: route('admin.analytics.funnels.index', {
        from: props.filters.from,
        to: props.filters.to,
    }),
    scenarios: route('admin.analytics.scenarios.index', {
        from: props.filters.from,
        to: props.filters.to,
    }),
    pages: route('admin.analytics.pages.index', {
        from: props.filters.from,
        to: props.filters.to,
    }),
    ctas: route('admin.analytics.ctas.index', {
        from: props.filters.from,
        to: props.filters.to,
    }),
    leadBoxes: route('admin.analytics.lead-boxes.index', {
        from: props.filters.from,
        to: props.filters.to,
    }),
    popups: route('admin.analytics.popups.index', {
        from: props.filters.from,
        to: props.filters.to,
    }),
    conversions: route('admin.analytics.conversions.index', {
        from: props.filters.from,
        to: props.filters.to,
    }),
    attribution: route('admin.analytics.attribution.index', {
        from: props.filters.from,
        to: props.filters.to,
    }),
}));

const analyticsMap = computed(() =>
    buildOverviewAnalyticsMap({
        readiness: props.readiness,
        overview: props.overview,
        links: {
            pages: categoryLinks.value.pages,
            ctas: categoryLinks.value.ctas,
            leadBoxes: categoryLinks.value.leadBoxes,
            popups: categoryLinks.value.popups,
            funnels: categoryLinks.value.funnels,
            scenarios: categoryLinks.value.scenarios,
            conversions: categoryLinks.value.conversions,
            attribution: categoryLinks.value.attribution,
        },
    }),
);

const metricRegistry = computed(() => analyticsMap.value.qbarMetrics);
const analyticsMetrics = computed(() => analyticsMap.value.metrics);
const heroMetrics = computed(() => analyticsMap.value.heroMetrics);
const categoryTiles = computed(() => analyticsMap.value.categoryTiles);

const clusterTileHref = (clusterKey: AnalyticsClusterKey) =>
    route('admin.analytics.clusters.show', {
        clusterKey,
        from: props.filters.from,
        to: props.filters.to,
    });

const clusterTileByKey = (clusterKey: AnalyticsClusterKey) =>
    categoryTiles.value.find((tile) => tile.key === clusterKey) ?? null;

const analyticsClusters = computed(() =>
    getAnalyticsClusters({
        from: props.filters.from,
        to: props.filters.to,
    }),
);

const clusterCards = computed<ClusterCard[]>(() =>
    analyticsClusters.value.map((cluster) => {
        const tile = clusterTileByKey(cluster.key);
        const metrics = tile?.quickViewMetricKeys
            .map((metricKey) =>
                metricRegistry.value.find((metric) => metric.key === metricKey),
            )
            .filter((metric): metric is QuickViewPayload => metric !== undefined)
            .slice(0, 3) ?? [];

        return {
            key: cluster.key,
            title: cluster.label,
            description: tile?.description ?? cluster.summaryShort,
            summaryShort: cluster.summaryShort,
            summaryFull: cluster.summaryFull,
            href: clusterTileHref(cluster.key),
            metricLine:
                tile?.metrics
                    .slice(0, 2)
                    .map((metric) => `${metric.label} ${metric.value}`)
                    .join(' · ') ?? 'No mapped preview is available for this selection.',
            primaryMetric: tile?.metrics[0] ?? null,
            secondaryMetric: tile?.metrics[1] ?? null,
            subClusters: cluster.subClusters,
            previewMetrics: metrics,
            links: tile?.links ?? [],
        };
    }),
);

const signalCards = computed(() =>
    heroMetrics.value
        .map((heroMetric) => {
            const quickViewMetric =
                metricRegistry.value.find((metric) => metric.key === heroMetric.key) ??
                null;

            return {
                ...heroMetric,
                quickViewMetric,
            };
        })
        .filter(
            (
                metric,
            ): metric is OverviewHeroMetric & { quickViewMetric: QuickViewPayload } =>
                metric.quickViewMetric !== null,
        )
        .slice(0, 2),
);

watch(
    metricRegistry,
    (metrics) => {
        activateAnalyticsMode({
            title: 'Analytics Focus',
            description:
                'The canonical QBar remains the metric switcher for Overview and now feeds the viewport when a signal card is selected.',
            metrics,
            selectedMetricKey: selectedMetricKey.value,
        });
    },
    { immediate: true },
);

watch(selectedAnalyticsMetric, (metric) => {
    if (!metric) {
        return;
    }

    selectedMetricKey.value = metric.key;

    if (selectedViewportItem.value.kind === 'metric') {
        selectedViewportItem.value = {
            kind: 'metric',
            key: metric.key,
        };
    }
});

watch(clusterCards, (cards) => {
    if (!cards.length) {
        return;
    }

    if (
        selectedViewportItem.value.kind === 'cluster' &&
        !cards.some((card) => card.key === selectedViewportItem.value.key)
    ) {
        selectedViewportItem.value = {
            kind: 'cluster',
            key: cards[0].key,
        };
    }
});

onBeforeUnmount(() => {
    deactivateAnalyticsMode();
});

const selectedQuickView = computed(
    () => selectedAnalyticsMetric.value ?? metricRegistry.value[0] ?? null,
);

const selectedViewportMetric = computed(() => {
    if (selectedViewportItem.value.kind !== 'metric') {
        return null;
    }

    return (
        metricRegistry.value.find(
            (metric) => metric.key === selectedViewportItem.value.key,
        ) ?? null
    );
});

const selectedClusterPreview = computed(() => {
    const clusterKey =
        selectedViewportItem.value.kind === 'cluster'
            ? selectedViewportItem.value.key
            : (selectedViewportMetric.value?.category ?? clusterCards.value[0]?.key);

    return (
        clusterCards.value.find((cluster) => cluster.key === clusterKey) ??
        clusterCards.value[0] ??
        null
    );
});

const orderedMetricPreviewSignals = (metrics: QuickViewPayload[]) => {
    if (!metrics.length) {
        return [];
    }

    const selectedKey = selectedViewportMetric.value?.key;

    return [...metrics].sort((left, right) => {
        if (left.key === selectedKey) {
            return -1;
        }

        if (right.key === selectedKey) {
            return 1;
        }

        return 0;
    });
};

const viewportSignals = computed(() => {
    if (selectedViewportMetric.value) {
        return orderedMetricPreviewSignals(
            metricRegistry.value
                .filter(
                    (metric) =>
                        metric.category === selectedViewportMetric.value?.category,
                )
                .slice(0, 3),
        );
    }

    return selectedClusterPreview.value?.previewMetrics.slice(0, 3) ?? [];
});

const viewportBreakdown = computed(() =>
    selectedViewportMetric.value?.breakdown?.slice(0, 4) ?? [],
);

const cueItems = computed(() => {
    const items: Array<{
        label: string;
        meta: string;
        href: string;
    }> = [];

    if (selectedClusterPreview.value) {
        items.push(
            ...selectedClusterPreview.value.subClusters.slice(0, 4).map((subCluster) => ({
                label: subCluster.label,
                meta: 'Sub-Cluster',
                href: subCluster.href,
            })),
        );
    }

    if (selectedViewportMetric.value) {
        items.push({
            label: selectedViewportMetric.value.drilldownLabel,
            meta: 'Metric',
            href: selectedViewportMetric.value.drilldownHref,
        });
    } else if (selectedClusterPreview.value) {
        items.push({
            label: `Open ${selectedClusterPreview.value.title}`,
            meta: 'Cluster',
            href: selectedClusterPreview.value.href,
        });
    }

    return items.slice(0, 5);
});

const miniReportRows = computed(() => [
    {
        label: 'Reference funnel',
        value: topFunnel.value?.label ?? 'No mapped preview is available for this selection.',
        note: topDropOff.value
            ? `Top drop-off: ${topDropOff.value.label}`
            : 'No drop-off summary is available for the selected range.',
    },
    {
        label: 'Scenario signal',
        value: topScenario.value?.label ?? 'No mapped preview is available for this selection.',
        note: topScenario.value
            ? `${formatPercent(topScenario.value.conversion_rate)} conversion rate`
            : 'No scenario conversion signal is available for the selected range.',
    },
    {
        label: 'Median time to conversion',
        value: formatDuration(
            props.overview.summary_cards.median_time_to_conversion_seconds,
        ),
        note: `Average ${formatDuration(props.overview.summary_cards.average_time_to_conversion_seconds)}`,
    },
]);

const selectClusterPreview = (clusterKey: AnalyticsClusterKey) => {
    selectedViewportItem.value = {
        kind: 'cluster',
        key: clusterKey,
    };
};

const selectMetricPreview = (metricKey: OverviewMetricKey) => {
    selectedViewportItem.value = {
        kind: 'metric',
        key: metricKey,
    };
    selectedMetricKey.value = metricKey;
    setAnalyticsMetric(metricKey);
};
</script>

<template>
    <Head title="Analytics Overview" />

    <AdminLayout>
        <div class="space-y-8">
            <AnalyticsHeader
                title="Overview"
                description="A compact control center for the analytics hierarchy, with the viewport holding focus while clusters, sub-clusters, and metric previews stay one click away."
                :filters="filters"
                :current-route="route('admin.analytics.index')"
            />

            <AnalyticsShell :has-right-rail="true">
                <template #main>
                    <section
                        class="grid gap-6 xl:grid-cols-[minmax(18rem,0.9fr),minmax(0,1.75fr)]"
                    >
                        <div class="space-y-4">
                            <section
                                class="rounded-[1.75rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.12),transparent_36%),linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.96))] p-5 shadow-sm shadow-slate-200/60"
                            >
                                <p
                                    class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                >
                                    Overview
                                </p>
                                <h2
                                    class="mt-2 text-2xl font-semibold tracking-tight text-slate-950"
                                >
                                    Overview Viewport System
                                </h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    The center viewport is now the primary screen.
                                    Cluster cards load previews into it, and the
                                    canonical QBar still controls metric focus.
                                </p>
                                <div
                                    v-if="overviewReport.paragraphs.length === 2"
                                    class="mt-4 space-y-3 rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-sm leading-6 text-slate-700"
                                >
                                    <p
                                        v-for="paragraph in overviewReport.paragraphs"
                                        :key="paragraph"
                                    >
                                        {{ paragraph }}
                                    </p>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-medium text-sky-700"
                                    >
                                        {{ overviewRangeLabel }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-medium text-slate-600"
                                    >
                                        {{ clusterCards.length }} clusters
                                    </span>
                                </div>

                                <div class="mt-5 grid gap-2">
                                    <div
                                        class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-3"
                                    >
                                        <p
                                            class="text-[10px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                        >
                                            Hierarchy
                                        </p>
                                        <p
                                            class="mt-2 text-sm font-medium text-slate-700"
                                        >
                                            Overview → Cluster → Sub-Cluster →
                                            Metric Group → Metric Modal
                                        </p>
                                    </div>
                                    <div
                                        class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-3"
                                    >
                                        <p
                                            class="text-[10px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                        >
                                            Active focus
                                        </p>
                                        <p
                                            class="mt-2 text-sm font-medium text-slate-700"
                                        >
                                            {{
                                                selectedViewportMetric?.label ??
                                                selectedClusterPreview?.title ??
                                                'No mapped preview is available for this selection.'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <button
                                v-for="signal in signalCards"
                                :key="signal.key"
                                type="button"
                                class="w-full rounded-[1.5rem] border p-4 text-left transition"
                                :class="
                                    selectedViewportItem.kind === 'metric' &&
                                    selectedViewportItem.key === signal.key
                                        ? 'border-slate-900 bg-slate-950 text-white shadow-lg shadow-slate-900/15'
                                        : 'border-slate-200 bg-white shadow-sm shadow-slate-200/60 hover:border-slate-300 hover:bg-slate-50'
                                "
                                @click="selectMetricPreview(signal.key)"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p
                                            class="text-[10px] font-semibold tracking-[0.18em] uppercase"
                                            :class="
                                                selectedViewportItem.kind === 'metric' &&
                                                selectedViewportItem.key === signal.key
                                                    ? 'text-white/70'
                                                    : 'text-slate-500'
                                            "
                                        >
                                            Signal
                                        </p>
                                        <h3 class="mt-2 text-base font-semibold">
                                            {{ signal.label }}
                                        </h3>
                                        <p
                                            class="mt-3 text-3xl font-semibold tracking-tight"
                                        >
                                            {{ signal.value }}
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold tracking-[0.18em] uppercase"
                                        :class="
                                            selectedViewportItem.kind === 'metric' &&
                                            selectedViewportItem.key === signal.key
                                                ? 'bg-white/10 text-white'
                                                : signal.tone === 'emerald'
                                                  ? 'bg-emerald-50 text-emerald-700'
                                                  : 'bg-sky-50 text-sky-700'
                                        "
                                    >
                                        Viewport
                                    </span>
                                </div>
                                <p
                                    class="mt-3 text-sm leading-6"
                                    :class="
                                        selectedViewportItem.kind === 'metric' &&
                                        selectedViewportItem.key === signal.key
                                            ? 'text-white/80'
                                            : 'text-slate-600'
                                    "
                                >
                                    {{ signal.hint }}
                                </p>
                            </button>

                            <RecentlyViewedCard
                                :current-href="currentOverviewHref"
                            />
                        </div>

                        <section
                            class="rounded-[2rem] border border-slate-200 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] p-6 shadow-[0_24px_50px_-30px_rgba(15,23,42,0.35)]"
                        >
                            <div
                                class="flex flex-col gap-4 border-b border-slate-200 pb-5 xl:flex-row xl:items-start xl:justify-between"
                            >
                                <div class="max-w-3xl">
                                    <p
                                        class="text-[11px] font-semibold tracking-[0.2em] text-slate-500 uppercase"
                                    >
                                        Main Viewport
                                    </p>
                                    <h2
                                        class="mt-2 text-3xl font-semibold tracking-tight text-slate-950"
                                    >
                                        {{
                                            selectedViewportMetric?.title ??
                                            selectedClusterPreview?.title ??
                                            'Analytics overview'
                                        }}
                                    </h2>
                                    <p
                                        class="mt-3 text-sm leading-6 text-slate-600"
                                    >
                                        {{
                                            selectedViewportMetric?.context ??
                                            selectedClusterPreview?.summaryFull ??
                                            'No mapped preview is available for this selection.'
                                        }}
                                    </p>
                                </div>

                                <Link
                                    :href="
                                        selectedViewportMetric?.drilldownHref ??
                                        selectedClusterPreview?.href ??
                                        route('admin.analytics.index')
                                    "
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                >
                                    {{
                                        selectedViewportMetric?.drilldownLabel ??
                                        (selectedClusterPreview
                                            ? `Open ${selectedClusterPreview.title}`
                                            : 'Open analytics')
                                    }}
                                </Link>
                            </div>

                            <template v-if="selectedViewportMetric">
                                <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.55fr),minmax(18rem,0.95fr)]">
                                    <section class="space-y-4">
                                        <div
                                            class="rounded-[1.6rem] border border-slate-200 bg-white p-5"
                                        >
                                            <div
                                                class="flex flex-wrap items-end justify-between gap-4"
                                            >
                                                <div>
                                                    <p
                                                        class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                                    >
                                                        Focus metric
                                                    </p>
                                                    <p
                                                        class="mt-3 text-5xl font-semibold tracking-tight text-slate-950"
                                                    >
                                                        {{ selectedViewportMetric.value }}
                                                    </p>
                                                </div>
                                                <div
                                                    v-if="selectedViewportMetric.meta"
                                                    class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600"
                                                >
                                                    {{ selectedViewportMetric.meta }}
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="rounded-[1.6rem] border border-slate-200 bg-white p-5"
                                        >
                                            <p
                                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                            >
                                                {{
                                                    selectedViewportMetric.supportingTitle ??
                                                    'Metric preview'
                                                }}
                                            </p>

                                            <div class="mt-4">
                                                <AnalyticsTrendChart
                                                    v-if="selectedViewportMetric.visual?.kind === 'trend'"
                                                    :rows="selectedViewportMetric.visual.rows"
                                                    :series="selectedViewportMetric.visual.series"
                                                />
                                                <AnalyticsBarComparison
                                                    v-else-if="selectedViewportMetric.visual?.kind === 'comparison'"
                                                    :rows="selectedViewportMetric.visual.rows"
                                                />
                                                <AnalyticsFunnelView
                                                    v-else-if="selectedViewportMetric.visual?.kind === 'funnel'"
                                                    :steps="selectedViewportMetric.visual.steps"
                                                />
                                                <AnalyticsMiniVisual
                                                    v-else-if="selectedViewportMetric.visual?.kind === 'mini'"
                                                    :items="selectedViewportMetric.visual.items"
                                                />
                                                <div
                                                    v-else
                                                    class="flex min-h-[15rem] items-center justify-center rounded-[1.25rem] border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-500"
                                                >
                                                    No mapped preview is available for this selection.
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <section
                                        class="rounded-[1.6rem] border border-slate-200 bg-slate-50/85 p-5"
                                    >
                                        <p
                                            class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                        >
                                            {{
                                                selectedViewportMetric.breakdownTitle ??
                                                'Supporting breakdown'
                                            }}
                                        </p>

                                        <div
                                            v-if="viewportBreakdown.length"
                                            class="mt-4 space-y-3"
                                        >
                                            <div
                                                v-for="item in viewportBreakdown"
                                                :key="`${selectedViewportMetric.key}-${item.label}`"
                                                class="rounded-2xl border border-slate-200 bg-white px-4 py-3"
                                            >
                                                <div
                                                    class="flex items-center justify-between gap-4"
                                                >
                                                    <p
                                                        class="text-sm font-medium text-slate-900"
                                                    >
                                                        {{ item.label }}
                                                    </p>
                                                    <p
                                                        class="text-sm font-semibold text-slate-700"
                                                    >
                                                        {{ item.value }}
                                                    </p>
                                                </div>
                                                <p
                                                    v-if="item.note"
                                                    class="mt-1 text-sm text-slate-500"
                                                >
                                                    {{ item.note }}
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="mt-4 rounded-[1.25rem] border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500"
                                        >
                                            No mapped preview is available for this selection.
                                        </div>
                                    </section>
                                </div>
                            </template>

                            <template v-else-if="selectedClusterPreview">
                                <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.45fr),minmax(18rem,0.95fr)]">
                                    <section
                                        class="rounded-[1.6rem] border border-slate-200 bg-white p-5"
                                    >
                                        <div
                                            class="flex flex-wrap items-start justify-between gap-4"
                                        >
                                            <div>
                                                <p
                                                    class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                                >
                                                    Cluster preview
                                                </p>
                                                <p
                                                    class="mt-2 text-lg font-semibold text-slate-950"
                                                >
                                                    {{ selectedClusterPreview.description }}
                                                </p>
                                            </div>
                                            <span
                                                class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600"
                                            >
                                                {{ selectedClusterPreview.subClusters.length }}
                                                sub-clusters
                                            </span>
                                        </div>

                                        <div class="mt-5 grid gap-3">
                                            <div
                                                v-for="subCluster in selectedClusterPreview.subClusters"
                                                :key="subCluster.key"
                                                class="rounded-[1.2rem] border border-slate-200 bg-slate-50 px-4 py-3 text-left transition hover:border-slate-300 hover:bg-white"
                                            >
                                                <div
                                                    class="flex items-center justify-between gap-4"
                                                >
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-sm font-semibold text-slate-900"
                                                        >
                                                            {{ subCluster.label }}
                                                        </p>
                                                        <p
                                                            class="mt-1 text-sm text-slate-500"
                                                        >
                                                            {{ subCluster.summaryShort }}
                                                        </p>
                                                    </div>
                                                    <Link
                                                        :href="subCluster.href"
                                                        class="shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                                                        @click.stop
                                                    >
                                                        Open
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <section
                                        class="rounded-[1.6rem] border border-slate-200 bg-slate-50/85 p-5"
                                    >
                                        <p
                                            class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                        >
                                            Control center context
                                        </p>
                                        <div class="mt-4 space-y-3">
                                            <div
                                                class="rounded-2xl border border-slate-200 bg-white px-4 py-3"
                                            >
                                                <p
                                                    class="text-[10px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                                >
                                                    Primary metric
                                                </p>
                                                <p
                                                    class="mt-2 text-lg font-semibold text-slate-900"
                                                >
                                                    {{
                                                        selectedClusterPreview.primaryMetric
                                                            ? `${selectedClusterPreview.primaryMetric.label} ${selectedClusterPreview.primaryMetric.value}`
                                                            : 'No mapped preview is available for this selection.'
                                                    }}
                                                </p>
                                            </div>
                                            <div
                                                class="rounded-2xl border border-slate-200 bg-white px-4 py-3"
                                            >
                                                <p
                                                    class="text-[10px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                                >
                                                    Secondary metric
                                                </p>
                                                <p
                                                    class="mt-2 text-sm font-semibold text-slate-900"
                                                >
                                                    {{
                                                        selectedClusterPreview.secondaryMetric
                                                            ? `${selectedClusterPreview.secondaryMetric.label} ${selectedClusterPreview.secondaryMetric.value}`
                                                            : 'No mapped preview is available for this selection.'
                                                    }}
                                                </p>
                                            </div>
                                            <div
                                                class="rounded-2xl border border-slate-200 bg-white px-4 py-3"
                                            >
                                                <p
                                                    class="text-[10px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                                >
                                                    Linked reports
                                                </p>
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <Link
                                                        v-for="link in selectedClusterPreview.links"
                                                        :key="`${selectedClusterPreview.key}-${link.label}`"
                                                        :href="link.href"
                                                        class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-white"
                                                    >
                                                        {{ link.label }}
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </template>

                            <section class="mt-6">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p
                                            class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                        >
                                            Viewport signals
                                        </p>
                                        <p class="mt-1 text-sm text-slate-600">
                                            Real metrics from the existing overview map keep the viewport grounded.
                                        </p>
                                    </div>
                                    <button
                                        v-if="selectedQuickView"
                                        type="button"
                                        class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                                        @click="selectMetricPreview(selectedQuickView.key)"
                                    >
                                        Load QBar focus
                                    </button>
                                </div>

                                <div
                                    v-if="viewportSignals.length"
                                    class="mt-4 grid gap-4 md:grid-cols-2 2xl:grid-cols-3"
                                >
                                    <MetricCard
                                        v-for="metric in viewportSignals"
                                        :key="metric.key"
                                        :metric="{
                                            key: metric.key,
                                            label: metric.label,
                                            value: metric.value,
                                            helper: metric.meta,
                                            description: metric.context,
                                        }"
                                        variant="compact"
                                        clickable
                                        @select="selectMetricPreview(metric.key)"
                                    />
                                </div>
                                <div
                                    v-else
                                    class="mt-4 rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500"
                                >
                                    No mapped preview is available for this selection.
                                </div>
                            </section>
                        </section>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr),minmax(18rem,0.95fr)]">
                        <section
                            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60"
                        >
                            <div
                                class="flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p
                                        class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                    >
                                        Cue strip
                                    </p>
                                    <h3
                                        class="mt-1 text-xl font-semibold tracking-tight text-slate-950"
                                    >
                                        Fast paths from the viewport
                                    </h3>
                                </div>
                                <p class="text-sm text-slate-500">
                                    Compact cues only. The QBar remains untouched.
                                </p>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3">
                                <Link
                                    v-for="item in cueItems"
                                    :key="`${item.meta}-${item.label}`"
                                    :href="item.href"
                                    class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white"
                                >
                                    <span
                                        class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                    >
                                        {{ item.meta }}
                                    </span>
                                    <span>{{ item.label }}</span>
                                </Link>
                            </div>
                        </section>

                        <section
                            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60"
                        >
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Mini report
                            </p>
                            <h3
                                class="mt-1 text-xl font-semibold tracking-tight text-slate-950"
                            >
                                Current range context
                            </h3>

                            <div class="mt-4 space-y-3">
                                <div
                                    v-for="row in miniReportRows"
                                    :key="row.label"
                                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                                >
                                    <div
                                        class="flex items-center justify-between gap-4"
                                    >
                                        <p class="text-sm font-medium text-slate-900">
                                            {{ row.label }}
                                        </p>
                                        <p class="text-sm font-semibold text-slate-700">
                                            {{ row.value }}
                                        </p>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ row.note }}
                                    </p>
                                </div>
                            </div>
                        </section>
                    </section>
                </template>

                <template #rail>
                    <section class="space-y-3">
                        <div class="px-1">
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Clusters
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">
                                The right rail is now a compact catalog. Select a card to preview it in the viewport, or use Open to navigate.
                            </p>
                        </div>

                        <article
                            v-for="cluster in clusterCards"
                            :key="cluster.key"
                            class="w-full rounded-[1.45rem] border p-3 text-left transition"
                            :class="
                                selectedClusterPreview?.key === cluster.key &&
                                selectedViewportItem.kind === 'cluster'
                                    ? 'border-slate-900 bg-slate-950 text-white shadow-lg shadow-slate-900/15'
                                    : 'border-slate-200 bg-white shadow-sm shadow-slate-200/50 hover:border-slate-300 hover:bg-slate-50/80'
                            "
                            role="button"
                            tabindex="0"
                            @click="selectClusterPreview(cluster.key)"
                            @keydown.enter.prevent="selectClusterPreview(cluster.key)"
                            @keydown.space.prevent="selectClusterPreview(cluster.key)"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold"
                                        :class="
                                            selectedClusterPreview?.key === cluster.key &&
                                            selectedViewportItem.kind === 'cluster'
                                                ? 'text-white'
                                                : 'text-slate-900'
                                        "
                                    >
                                        {{ cluster.title }}
                                    </p>
                                    <p
                                        class="mt-1 line-clamp-2 text-[12px] leading-5"
                                        :class="
                                            selectedClusterPreview?.key === cluster.key &&
                                            selectedViewportItem.kind === 'cluster'
                                                ? 'text-white/75'
                                                : 'text-slate-600'
                                        "
                                    >
                                        {{ cluster.description }}
                                    </p>
                                </div>
                                <Link
                                    :href="cluster.href"
                                    class="shrink-0 rounded-full border px-3 py-1 text-[11px] font-medium transition"
                                    :class="
                                        selectedClusterPreview?.key === cluster.key &&
                                        selectedViewportItem.kind === 'cluster'
                                            ? 'border-white/20 bg-white/10 text-white hover:bg-white/15'
                                            : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:bg-white'
                                    "
                                    @click.stop
                                >
                                    Open
                                </Link>
                            </div>

                            <div
                                class="mt-3 flex items-center justify-between gap-3 rounded-[1.1rem] border px-3 py-2"
                                :class="
                                    selectedClusterPreview?.key === cluster.key &&
                                    selectedViewportItem.kind === 'cluster'
                                        ? 'border-white/10 bg-white/5'
                                        : 'border-slate-200 bg-slate-50/80'
                                "
                            >
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-semibold tracking-[0.18em] uppercase"
                                        :class="
                                            selectedClusterPreview?.key === cluster.key &&
                                            selectedViewportItem.kind === 'cluster'
                                                ? 'text-white/60'
                                                : 'text-slate-500'
                                        "
                                    >
                                        Signal
                                    </p>
                                    <p
                                        class="mt-1 truncate text-sm font-semibold"
                                        :class="
                                            selectedClusterPreview?.key === cluster.key &&
                                            selectedViewportItem.kind === 'cluster'
                                                ? 'text-white'
                                                : 'text-slate-900'
                                        "
                                    >
                                        {{
                                            cluster.primaryMetric
                                                ? `${cluster.primaryMetric.label} ${cluster.primaryMetric.value}`
                                                : 'No mapped preview is available for this selection.'
                                        }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full border"
                                        :class="
                                            selectedClusterPreview?.key === cluster.key &&
                                            selectedViewportItem.kind === 'cluster'
                                                ? 'border-white/15 bg-white/10'
                                                : 'border-slate-200 bg-white'
                                        "
                                    >
                                        <div
                                            class="h-6 w-6 rounded-full border-4 border-sky-300 border-r-transparent"
                                        />
                                    </div>
                                    <div class="flex h-10 items-end gap-1">
                                        <span
                                            class="w-1.5 rounded-full bg-slate-300"
                                            style="height: 32%"
                                        />
                                        <span
                                            class="w-1.5 rounded-full bg-sky-400"
                                            style="height: 58%"
                                        />
                                        <span
                                            class="w-1.5 rounded-full bg-slate-900"
                                            style="height: 78%"
                                        />
                                    </div>
                                </div>
                            </div>

                            <p
                                class="mt-3 truncate text-[11px]"
                                :class="
                                    selectedClusterPreview?.key === cluster.key &&
                                    selectedViewportItem.kind === 'cluster'
                                        ? 'text-white/70'
                                        : 'text-slate-500'
                                "
                            >
                                {{ cluster.metricLine }}
                            </p>
                        </article>
                    </section>
                </template>
            </AnalyticsShell>
        </div>
    </AdminLayout>
</template>
