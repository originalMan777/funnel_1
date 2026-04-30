<script setup lang="ts">
type TableRow = {
    source: string;
    leads: number;
    conversion: string;
    time: string;
    status: 'Strong' | 'Stable' | 'Weak';
    color: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: TableRow[];
    }>(),
    {
        title: 'SOURCE PERFORMANCE',
        subtitle: 'Structured lead source breakdown',
        data: () => [
            { source: 'Google Search', leads: 284, conversion: '18.4%', time: '2m 14s', status: 'Strong', color: '#22c55e' },
            { source: 'LinkedIn', leads: 196, conversion: '14.1%', time: '3m 02s', status: 'Strong', color: '#22c55e' },
            { source: 'Direct', leads: 142, conversion: '9.8%', time: '1m 48s', status: 'Stable', color: '#fde047' },
            { source: 'Facebook Ads', leads: 91, conversion: '5.7%', time: '0m 52s', status: 'Weak', color: '#f87171' },
        ],
    },
);
</script>

<template>
    <div class="w-full max-w-6xl mx-auto rounded-2xl border border-white/10 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-8 shadow-2xl shadow-black/40">
        <div class="relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.04] p-6">
            <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-blue-500/12 blur-3xl" />
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-56 w-56 rounded-full bg-cyan-400/10 blur-3xl" />

            <div class="relative mb-6 flex items-start justify-between gap-6">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">
                        {{ title }}
                    </div>

                    <div class="mt-2 text-xs text-white/35">
                        {{ subtitle }}
                    </div>
                </div>

                <div class="border border-white bg-white px-3 py-1 text-xs font-semibold text-slate-950">
                    TABLE
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-white/15 bg-black/25">
                <div class="grid grid-cols-[1.5fr_0.8fr_0.9fr_0.9fr_0.9fr] border-b border-white/15 bg-white/[0.06] px-4 py-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/40">
                    <div>Source</div>
                    <div class="text-right">Leads</div>
                    <div class="text-right">Conv.</div>
                    <div class="text-right">Time</div>
                    <div class="text-right">Status</div>
                </div>

                <div
                    v-for="row in data"
                    :key="row.source"
                    class="grid grid-cols-[1.5fr_0.8fr_0.9fr_0.9fr_0.9fr] items-center border-b border-white/10 px-4 py-4 last:border-b-0"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-2.5 w-2.5 rounded-sm border border-white/40"
                            :style="{ background: row.color }"
                        />

                        <div class="text-sm font-semibold text-white">
                            {{ row.source }}
                        </div>
                    </div>

                    <div class="text-right text-sm font-semibold text-white/80">
                        {{ row.leads }}
                    </div>

                    <div class="text-right text-sm font-semibold text-white/80">
                        {{ row.conversion }}
                    </div>

                    <div class="text-right text-sm text-white/55">
                        {{ row.time }}
                    </div>

                    <div class="flex justify-end">
                        <span
                            class="border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em]"
                            :style="{
                                color: row.color,
                                borderColor: row.color + '80',
                                background: row.color + '18',
                            }"
                        >
                            {{ row.status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
