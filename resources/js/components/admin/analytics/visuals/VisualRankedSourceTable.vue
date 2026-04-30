<script setup lang="ts">
type RankedItem = {
    rank: number;
    label: string;
    value: number;
    color: string;
    meta?: string;
    change?: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: RankedItem[];
    }>(),
    {
        title: 'SOURCE RANKING',
        subtitle: 'Top performers by conversion signal',
        data: () => [
            { rank: 1, label: 'Google Search', value: 82, color: '#2563eb', meta: 'Lead quality', change: '+12%' },
            { rank: 2, label: 'LinkedIn', value: 71, color: '#7c3aed', meta: 'B2B intent', change: '+8%' },
            { rank: 3, label: 'Direct Traffic', value: 58, color: '#16a34a', meta: 'Returning visitors', change: '+4%' },
            { rank: 4, label: 'Facebook Ads', value: 43, color: '#dc2626', meta: 'Cold traffic', change: '-3%' },
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
                RANKING
            </div>
        </div>

        <div class="overflow-hidden border border-white/25 bg-white/[0.03]">
            <div class="grid grid-cols-[4rem_1.4fr_1.5fr_5rem_5rem] border-b border-white/15 bg-white/[0.06] px-4 py-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/45">
                <div>Rank</div>
                <div>Source</div>
                <div>Signal</div>
                <div class="text-right">Score</div>
                <div class="text-right">Move</div>
            </div>

            <div
                v-for="item in data"
                :key="item.label"
                class="grid grid-cols-[4rem_1.4fr_1.5fr_5rem_5rem] items-center border-b border-white/10 px-4 py-4 last:border-b-0"
            >
                <div>
                    <div class="flex h-8 w-8 items-center justify-center border border-white/35 bg-black/30 text-sm font-semibold text-white">
                        {{ item.rank }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold text-white">
                        {{ item.label }}
                    </div>

                    <div class="mt-1 text-xs text-white/35">
                        {{ item.meta }}
                    </div>
                </div>

                <div class="pr-5">
                    <div class="relative h-7 overflow-hidden rounded-md border border-white/25 bg-black/35">
                        <div
                            class="absolute inset-y-0 left-0 rounded-md border-r border-white/50"
                            :style="{
                                width: `${(item.value / maxValue) * 100}%`,
                                background: `linear-gradient(90deg, ${item.color} 0%, ${item.color} 68%, color-mix(in srgb, ${item.color} 78%, white 22%) 100%)`,
                            }"
                        />

                        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.10)_1px,transparent_1px)] bg-[length:12.5%_100%]" />
                    </div>
                </div>

                <div
                    class="text-right text-lg font-semibold"
                    :style="{ color: item.color }"
                >
                    {{ item.value }}
                </div>

                <div
                    class="text-right text-sm font-semibold"
                    :class="item.change?.startsWith('-') ? 'text-red-300' : 'text-green-300'"
                >
                    {{ item.change }}
                </div>
            </div>
        </div>
    </div>
</template>
