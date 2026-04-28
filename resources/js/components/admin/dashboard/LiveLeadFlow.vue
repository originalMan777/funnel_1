<script setup lang="ts">
type FlowItem = {
    key: string;
    label: string;
    count: string | number;
    delta: string;
    status?: string;
    detail?: string;
};

type FlowVisual = 'premium-vertical-bar' | 'funnel-flow' | 'mini-report-card' | 'premium-donut';

defineProps<{
    items: FlowItem[];
}>();

const visualByKey: Record<string, { type: FlowVisual; number: string; label: string }> = {
    visitors: {
        type: 'premium-vertical-bar',
        number: '12',
        label: 'Traffic volume',
    },
    engaged: {
        type: 'funnel-flow',
        number: '05',
        label: 'Movement path',
    },
    leads: {
        type: 'mini-report-card',
        number: '23',
        label: 'Lead snapshot',
    },
    converted: {
        type: 'premium-donut',
        number: '01',
        label: 'Conversion proof',
    },
};

const visualFor = (item: FlowItem) =>
    visualByKey[item.key] ?? {
        type: 'mini-report-card',
        number: '23',
        label: 'Metric snapshot',
    };

const progressFor = (index: number) => `${Math.max(28, 92 - index * 18)}%`;
</script>

<template>
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Live Lead Flow</p>
                <h2 class="mt-1 text-xl font-black tracking-tight text-slate-950">AMC movement right now</h2>
            </div>
            <p class="max-w-xl text-sm leading-6 text-slate-500">
                A fast read of how traffic is moving through acquisition, management, and conversion.
            </p>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-4">
            <div v-for="(item, index) in items" :key="item.key" class="relative">
                <div
                    class="h-full rounded-2xl border border-slate-200 bg-slate-50 p-4 transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">{{ item.label }}</p>
                            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ item.count }}</p>
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-emerald-700">
                                {{ item.delta }}
                            </span>

                            <div class="flex h-14 w-16 items-center justify-center rounded-2xl border border-sky-100 bg-white shadow-sm">
                                <svg
                                    v-if="visualFor(item).type === 'premium-vertical-bar'"
                                    viewBox="0 0 64 48"
                                    class="h-10 w-12"
                                    aria-hidden="true"
                                >
                                    <rect x="8" y="23" width="10" height="17" rx="5" class="fill-slate-200" />
                                    <rect x="27" y="10" width="12" height="30" rx="6" class="fill-sky-500" />
                                    <rect x="48" y="17" width="10" height="23" rx="5" class="fill-slate-300" />
                                </svg>

                                <svg
                                    v-else-if="visualFor(item).type === 'funnel-flow'"
                                    viewBox="0 0 72 48"
                                    class="h-10 w-12"
                                    aria-hidden="true"
                                >
                                    <rect x="8" y="7" width="56" height="7" rx="3.5" class="fill-sky-300" />
                                    <rect x="14" y="19" width="44" height="7" rx="3.5" class="fill-sky-400" />
                                    <rect x="21" y="31" width="30" height="7" rx="3.5" class="fill-sky-700" />
                                </svg>

                                <svg
                                    v-else-if="visualFor(item).type === 'premium-donut'"
                                    viewBox="0 0 60 60"
                                    class="h-11 w-11"
                                    aria-hidden="true"
                                >
                                    <circle cx="30" cy="30" r="18" fill="none" stroke="rgb(226 232 240)" stroke-width="9" />
                                    <circle
                                        cx="30"
                                        cy="30"
                                        r="18"
                                        fill="none"
                                        stroke="rgb(14 165 233)"
                                        stroke-width="9"
                                        stroke-linecap="round"
                                        stroke-dasharray="76 114"
                                        transform="rotate(-90 30 30)"
                                    />
                                    <circle cx="30" cy="30" r="9" class="fill-white" />
                                </svg>

                                <svg
                                    v-else
                                    viewBox="0 0 64 48"
                                    class="h-10 w-12"
                                    aria-hidden="true"
                                >
                                    <rect x="10" y="8" width="44" height="32" rx="10" class="fill-white stroke-slate-200" stroke-width="2" />
                                    <rect x="18" y="17" width="18" height="4" rx="2" class="fill-slate-300" />
                                    <rect x="18" y="26" width="28" height="5" rx="2.5" class="fill-sky-400" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <span class="rounded-full border border-sky-100 bg-sky-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-[0.14em] text-sky-700">
                            {{ visualFor(item).number }}
                        </span>
                        <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">
                            {{ visualFor(item).label }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm leading-5 text-slate-600">{{ item.detail }}</p>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200">
                        <div
                            class="h-full rounded-full bg-slate-950"
                            :style="{ width: progressFor(index) }"
                        ></div>
                    </div>
                </div>

                <div
                    v-if="index < items.length - 1"
                    class="pointer-events-none absolute -right-2 top-1/2 z-10 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-sm font-black text-slate-400 shadow-sm lg:flex"
                >
                    →
                </div>
            </div>
        </div>
    </section>
</template>
