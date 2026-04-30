<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import VisualConsoleDisplayNumber from '@/components/admin/analytics/visuals/VisualConsoleDisplayNumber.vue';
import VisualHalfDonutMovingDot from '@/components/admin/analytics/visuals/VisualHalfDonutMovingDot.vue';
import VisualHeavyDonutDistribution from '@/components/admin/analytics/visuals/VisualHeavyDonutDistribution.vue';
import VisualScreenTileNumber from '@/components/admin/analytics/visuals/VisualScreenTileNumber.vue';
import VisualStraightStrengthTrack from '@/components/admin/analytics/visuals/VisualStraightStrengthTrack.vue';
import VisualTacticalDonutDistribution from '@/components/admin/analytics/visuals/VisualTacticalDonutDistribution.vue';

const visualCandidates = [
    {
        key: 'screen-tile-number',
        title: 'Screen Tile Number',
        eyebrow: 'Number display / accepted',
        label: 'Total Leads',
        value: '1,284',
        meta: 'Current captured volume',
        foot: 'Raw number display',
        status: 'Accepted',
        component: VisualScreenTileNumber,
    },
    {
        key: 'console-display-number',
        title: 'Console Display Number',
        eyebrow: 'Number display / accepted',
        label: 'Pipeline Value',
        value: '$128K',
        meta: 'Qualified opportunity total',
        foot: 'Operator display panel',
        status: 'Accepted',
        component: VisualConsoleDisplayNumber,
    },
    {
        key: 'arc-dot-indicator',
        title: 'Half Donut — Moving Dot',
        eyebrow: 'Strength display / accepted',
        value: '72',
        label: 'Signal Strength',
        meta: 'Dot travels across the arc',
        foot: undefined,
        status: 'Accepted',
        component: VisualHalfDonutMovingDot,
    },
    {
        key: 'flat-line-indicator',
        title: 'Straight Strength Track / Hard Marker',
        eyebrow: 'Strength display / accepted',
        value: '72',
        label: 'Signal Strength',
        meta: 'Hard marker across strength track',
        foot: undefined,
        status: 'Accepted',
        component: VisualStraightStrengthTrack,
    },
    {
        key: 'heavy-donut',
        title: 'Donut — Heavy Segments',
        eyebrow: 'Distribution / sandbox',
        label: 'Traffic Sources',
        value: undefined,
        meta: 'Bold distribution visual',
        foot: undefined,
        status: 'Testing',
        component: VisualHeavyDonutDistribution,
        data: [
            { label: 'Google', value: 50, color: '#22c55e' },
            { label: 'Facebook', value: 30, color: '#f59e0b' },
            { label: 'Direct', value: 20, color: '#ef4444' },
        ],
    },
    {
        key: 'tactical-donut',
        title: 'Donut — Tactical Blocks',
        eyebrow: 'Distribution / sandbox',
        label: 'Traffic Sources',
        value: undefined,
        meta: 'Immediate readability test',
        foot: undefined,
        status: 'Testing',
        component: VisualTacticalDonutDistribution,
        data: [
            { label: 'Google', value: 50, color: '#84cc16' },
            { label: 'Facebook', value: 30, color: '#eab308' },
            { label: 'Direct', value: 20, color: '#dc2626' },
        ],
    },
];
</script>

<template>
    <Head title="Graphics Lab" />

    <AdminLayout>
        <div class="min-h-screen bg-[#f4efe7] px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="rounded-[1.75rem] border border-stone-300 bg-white px-6 py-8 shadow-sm sm:px-8">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-stone-500">
                        Analytics Sandbox
                    </p>

                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-stone-950 sm:text-4xl">
                        Graphics Lab
                    </h1>

                    <p class="mt-3 max-w-3xl text-sm leading-6 text-stone-600 sm:text-base">
                        Gallery overview for analytics visual candidates. Use the Visual Workbench to focus on one visual at a time.
                    </p>
                </section>

                <section class="overflow-hidden rounded-[1.75rem] border border-slate-800 bg-slate-950 shadow-2xl">
                    <div class="border-b border-white/10 px-6 py-5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-300">
                            Visual Gallery
                        </p>

                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-white">
                            Analytics Visuals Hub Candidates
                        </h2>
                    </div>

                    <div class="grid gap-px bg-white/10 lg:grid-cols-2">
                        <article
                            v-for="candidate in visualCandidates"
                            :key="candidate.key"
                            class="min-h-[32rem] bg-slate-950 p-6"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-500">
                                        {{ candidate.eyebrow }}
                                    </p>

                                    <h3 class="mt-2 text-lg font-semibold tracking-tight text-white">
                                        {{ candidate.title }}
                                    </h3>
                                </div>

                                <span class="rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-cyan-100">
                                    {{ candidate.status }}
                                </span>
                            </div>

                            <component
                                :is="candidate.component"
                                :data="candidate.data"
                                :foot="candidate.foot"
                                :label="candidate.label"
                                :meta="candidate.meta"
                                :value="candidate.value"
                            />
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
