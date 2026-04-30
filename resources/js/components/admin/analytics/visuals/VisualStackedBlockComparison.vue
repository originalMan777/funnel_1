<script setup lang="ts">
type BlockItem = {
    label: string;
    value: number;
    color: string;
    meta?: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: BlockItem[];
    }>(),
    {
        title: 'SOURCE STACK',
        subtitle: 'Stacked comparison blocks',
        data: () => [
            { label: 'Google', value: 82, color: '#2563eb', meta: 'Highest signal' },
            { label: 'Facebook', value: 64, color: '#7c3aed', meta: 'Strong mid-tier' },
            { label: 'Direct', value: 48, color: '#16a34a', meta: 'Stable baseline' },
            { label: 'Referral', value: 31, color: '#dc2626', meta: 'Needs lift' },
        ],
    },
);

const maxValue = Math.max(...props.data.map((item) => item.value), 100);
</script>

<template>
    <div class="w-full max-w-6xl mx-auto border border-white/10 bg-[#020617] p-10">
        <div class="mb-8 flex items-start justify-between">
            <div>
                <div class="text-xs font-semibold tracking-[0.24em] text-white/60">
                    {{ title }}
                </div>

                <div class="mt-1 text-sm text-white/40">
                    {{ subtitle }}
                </div>
            </div>

            <div class="border border-white bg-white px-3 py-1 text-xs font-semibold text-slate-950">
                COMPARISON
            </div>
        </div>

        <!-- FULL WIDTH BAR FIELD -->
        <div class="border border-white/30 bg-white/[0.03] p-5">
            <div class="space-y-3">
                <div
                    v-for="item in data"
                    :key="item.label"
                    class="relative h-[51px] overflow-hidden rounded-md border border-white/35 bg-black/35"
                >
                    <div
                        class="absolute inset-y-0 left-0 rounded-md border-r border-white/60"
                        :style="{
                            width: `${(item.value / maxValue) * 100}%`,
                            background: `linear-gradient(90deg, ${item.color} 0%, ${item.color} 68%, color-mix(in srgb, ${item.color} 78%, white 22%) 100%)`,
                        }"
                    />

                    <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.12)_1px,transparent_1px)] bg-[length:10%_100%]" />

                    <div class="absolute inset-y-0 left-5 flex items-center">
                        <span class="text-sm font-semibold text-white drop-shadow">
                            {{ item.label }}
                        </span>
                    </div>

                    <div class="absolute inset-y-0 right-5 flex items-center">
                        <span class="text-xl font-semibold text-white">
                            {{ item.value }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- COMPACT INDEX UNDERNEATH -->
        <div class="mt-5 flex overflow-hidden border border-white/20 bg-white/[0.035]">
            <div class="flex w-10 shrink-0 items-center justify-center border-r border-white/20 bg-white/[0.06]">
                <div class="-rotate-90 text-[10px] font-semibold uppercase tracking-[0.24em] text-white/45">
                    Index
                </div>
            </div>

            <div class="grid flex-1 gap-2 p-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="item in data"
                    :key="`${item.label}-index`"
                    class="flex items-center gap-3 border border-white/15 bg-black/20 px-3 py-2"
                >
                    <div
                        class="h-3 w-3 shrink-0 rounded-[2px] border border-white/50"
                        :style="{ background: item.color }"
                    />

                    <div class="min-w-0">
                        <div class="truncate text-xs font-semibold text-white">
                            {{ item.label }} · {{ item.value }}%
                        </div>

                        <div class="truncate text-[11px] leading-4 text-white/35">
                            {{ item.meta }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
