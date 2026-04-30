<script setup lang="ts">
type HeatPoint = {
    label: string;
    value: number;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: HeatPoint[];
    }>(),
    {
        title: 'INTENSITY STRIP',
        subtitle: 'Activity concentration across one sequence',
        data: () => [
            { label: '12a', value: 2 },
            { label: '3a', value: 4 },
            { label: '6a', value: 9 },
            { label: '9a', value: 18 },
            { label: '12p', value: 26 },
            { label: '3p', value: 31 },
            { label: '6p', value: 21 },
            { label: '9p', value: 12 },
        ],
    },
);

const max = Math.max(...props.data.map((item) => item.value), 1);

const getCellStyle = (value: number) => {
    const t = value / max;

    return {
        background: `linear-gradient(180deg,
            rgba(34,197,94,${0.2 + t * 0.62}) 0%,
            rgba(34,197,94,${0.08 + t * 0.35}) 100%)`,
        boxShadow: t > 0.75 ? `0 0 18px rgba(34,197,94,${0.25 + t * 0.25})` : 'none',
    };
};
</script>

<template>
    <div class="w-full max-w-5xl mx-auto rounded-2xl border border-white/10 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-8 shadow-2xl shadow-black/40">
        <div class="relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.04] p-6">
            <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-green-400/10 blur-3xl" />
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-56 w-56 rounded-full bg-emerald-400/10 blur-3xl" />

            <div class="mb-7 flex items-start justify-between gap-6">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">
                        {{ title }}
                    </div>

                    <div class="mt-2 text-xs text-white/35">
                        {{ subtitle }}
                    </div>
                </div>

                <div class="border border-white bg-white px-3 py-1 text-xs font-semibold text-slate-950">
                    HEAT
                </div>
            </div>

            <div class="rounded-xl border border-white/15 bg-black/25 p-5">
                <div class="grid grid-cols-8 gap-2">
                    <div
                        v-for="item in data"
                        :key="item.label"
                        class="relative h-28 overflow-hidden rounded-md border border-white/10"
                        :style="getCellStyle(item.value)"
                    >
                        <div class="absolute inset-x-0 bottom-3 text-center">
                            <div class="text-lg font-semibold text-white">
                                {{ item.value }}
                            </div>

                            <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/40">
                                {{ item.label }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/35">
                        Low
                    </span>

                    <div class="h-3 flex-1 overflow-hidden rounded-full border border-white/20 bg-black/35">
                        <div class="h-full w-full bg-gradient-to-r from-white/10 via-green-700 to-green-300" />
                    </div>

                    <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/35">
                        High
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
