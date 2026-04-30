<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: number[][];
        xLabels?: string[];
        yLabels?: string[];
    }>(),
    {
        title: 'HEATMAP',
        subtitle: 'User activity by day and hour',
        data: () => [
            [2, 5, 8, 4, 3, 1, 0],
            [3, 6, 12, 8, 5, 2, 1],
            [5, 10, 18, 14, 9, 4, 2],
            [6, 12, 22, 18, 11, 5, 3],
            [4, 9, 16, 12, 8, 3, 1],
        ],
        xLabels: () => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        yLabels: () => ['Morning', 'Midday', 'Afternoon', 'Evening', 'Night'],
    },
);

const flat = props.data.flat();
const max = Math.max(...flat, 1);

const getColor = (value: number) => {
    const t = value / max;

    if (t === 0) return 'rgba(255,255,255,0.05)';

    return `linear-gradient(180deg,
        rgba(34,197,94,${0.25 + t * 0.5}) 0%,
        rgba(34,197,94,${0.1 + t * 0.3}) 100%)`;
};
</script>

<template>
    <div class="w-full max-w-5xl mx-auto rounded-2xl border border-white/10 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-8 shadow-2xl shadow-black/40">
        <div class="relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.04] p-6">
            <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-green-400/10 blur-3xl" />
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-56 w-56 rounded-full bg-emerald-400/10 blur-3xl" />

            <div class="mb-6 flex items-start justify-between gap-6">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">
                        {{ title }}
                    </div>

                    <div class="mt-2 text-xs text-white/35">
                        {{ subtitle }}
                    </div>
                </div>

                <div class="border border-white bg-white px-3 py-1 text-xs font-semibold text-slate-950">
                    DENSITY
                </div>
            </div>

            <div class="relative rounded-xl border border-white/15 bg-black/25 p-5">
                <div class="flex gap-4">
                    <!-- Y AXIS -->
                    <div class="flex flex-col justify-between pt-0 text-right text-[10px] font-semibold uppercase tracking-[0.12em] text-white/35">
                        <div
                            v-for="label in yLabels"
                            :key="label"
                            class="flex h-10 items-center justify-end whitespace-nowrap"
                        >
                            {{ label }}
                        </div>
                    </div>

                    <!-- GRID + X AXIS -->
                    <div class="min-w-0 flex-1">
                        <div class="grid gap-2">
                            <div
                                v-for="(row, rowIndex) in data"
                                :key="rowIndex"
                                class="grid grid-cols-7 gap-2"
                            >
                                <div
                                    v-for="(cell, colIndex) in row"
                                    :key="colIndex"
                                    class="relative h-10 rounded-md border border-white/10"
                                    :style="{ background: getColor(cell) }"
                                    :title="`${yLabels[rowIndex]} / ${xLabels[colIndex]}: ${cell}`"
                                >
                                    <div
                                        v-if="cell === max"
                                        class="absolute inset-0 rounded-md border border-white/45 shadow-[0_0_16px_rgba(34,197,94,0.45)]"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-7 gap-2 text-center text-[10px] font-semibold uppercase tracking-[0.12em] text-white/35">
                            <div
                                v-for="label in xLabels"
                                :key="label"
                            >
                                {{ label }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LEGEND -->
                <div class="mt-6 flex items-center gap-3">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/35">
                        Low
                    </div>

                    <div class="h-3 flex-1 overflow-hidden rounded-full border border-white/20 bg-black/35">
                        <div class="h-full w-full bg-gradient-to-r from-white/10 via-green-700 to-green-300" />
                    </div>

                    <div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/35">
                        High
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
