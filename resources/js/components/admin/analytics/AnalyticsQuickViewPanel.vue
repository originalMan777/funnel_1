<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AnalyticsBarComparison from '@/components/admin/analytics/AnalyticsBarComparison.vue';
import AnalyticsFunnelView from '@/components/admin/analytics/AnalyticsFunnelView.vue';
import AnalyticsMiniVisual from '@/components/admin/analytics/AnalyticsMiniVisual.vue';
import AnalyticsTrendChart from '@/components/admin/analytics/AnalyticsTrendChart.vue';
import type { QuickViewPayload } from '@/components/admin/analytics/metricRegistry';

defineProps<{
    metric: QuickViewPayload | null;
}>();
</script>

<template>
    <section
        class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60"
    >
        <template v-if="metric">
            <div
                class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between"
            >
                <div class="max-w-2xl">
                    <p
                        class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                    >
                        Quick View
                    </p>
                    <h2
                        class="mt-2 text-2xl font-semibold tracking-tight text-slate-900"
                    >
                        {{ metric.title }}
                    </h2>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <p
                            class="text-4xl font-semibold tracking-tight text-slate-950"
                        >
                            {{ metric.value }}
                        </p>
                        <div
                            v-if="metric.deltaLabel && metric.deltaValue"
                            class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm text-slate-700"
                        >
                            <span class="font-medium text-slate-500">{{
                                metric.deltaLabel
                            }}</span>
                            <span class="font-semibold text-slate-900">{{
                                metric.deltaValue
                            }}</span>
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        {{ metric.context }}
                    </p>
                </div>

                <Link
                    :href="metric.drilldownHref"
                    class="inline-flex rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    {{ metric.drilldownLabel }}
                </Link>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-[1.6fr,0.9fr]">
                <div class="space-y-3">
                    <p
                        v-if="metric.supportingTitle"
                        class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                    >
                        {{ metric.supportingTitle }}
                    </p>
                    <AnalyticsTrendChart
                        v-if="metric.visual?.kind === 'trend'"
                        :rows="metric.visual.rows"
                        :series="metric.visual.series"
                    />
                    <div
                        v-else-if="metric.visual?.kind === 'comparison'"
                        class="rounded-[1.5rem] border border-slate-200 bg-white p-5"
                    >
                        <AnalyticsBarComparison :rows="metric.visual.rows" />
                    </div>
                    <div
                        v-else-if="metric.visual?.kind === 'funnel'"
                        class="rounded-[1.5rem] border border-slate-200 bg-white p-5"
                    >
                        <AnalyticsFunnelView :steps="metric.visual.steps" />
                    </div>
                    <div
                        v-else-if="metric.visual?.kind === 'mini'"
                        class="rounded-[1.5rem] border border-slate-200 bg-white p-5"
                    >
                        <AnalyticsMiniVisual :items="metric.visual.items" />
                    </div>
                    <div
                        v-else
                        class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500"
                    >
                        No focused preview is available for this metric yet.
                    </div>
                </div>

                <div class="rounded-[1.5rem] bg-slate-50 p-5">
                    <p
                        class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                    >
                        {{ metric.breakdownTitle || 'Supporting Breakdown' }}
                    </p>
                    <div v-if="metric.breakdown?.length" class="mt-4 space-y-3">
                        <div
                            v-for="item in metric.breakdown"
                            :key="`${metric.key}-${item.label}`"
                            class="rounded-2xl bg-white px-4 py-3"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <p class="text-sm font-medium text-slate-900">
                                    {{ item.label }}
                                </p>
                                <p class="text-sm font-semibold text-slate-700">
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
                    <p v-else class="mt-4 text-sm text-slate-500">
                        No supporting breakdown is available for this metric in
                        the selected range.
                    </p>
                </div>
            </div>
        </template>

        <template v-else>
            <div
                class="flex min-h-[240px] items-center justify-center text-sm text-slate-400"
            >
                Select a metric to inspect.
            </div>
        </template>
    </section>
</template>
