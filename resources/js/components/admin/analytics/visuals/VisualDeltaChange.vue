<script setup lang="ts">
type DeltaItem = {
    label: string;
    previous: number;
    current: number;
    color: string;
    meta?: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: DeltaItem[];
    }>(),
    {
        title: 'DELTA CHANGE',
        subtitle: 'Movement from previous period',
        data: () => [
            { label: 'Google', previous: 64, current: 82, color: '#2563eb', meta: 'Search traffic lift' },
            { label: 'LinkedIn', previous: 58, current: 71, color: '#7c3aed', meta: 'B2B intent rising' },
            { label: 'Direct', previous: 61, current: 54, color: '#16a34a', meta: 'Returning visitors dipped' },
            { label: 'Facebook', previous: 49, current: 43, color: '#dc2626', meta: 'Cold traffic down' },
        ],
    },
);

const maxValue = Math.max(...props.data.map((item) => item.current), 100);

const deltaFor = (item: DeltaItem) => item.current - item.previous;
const isUp = (item: DeltaItem) => deltaFor(item) >= 0;
const deltaColor = (item: DeltaItem) => (isUp(item) ? '#86efac' : '#fca5a5');
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
                DELTA
            </div>
        </div>

        <div class="space-y-3">
            <div
                v-for="item in data"
                :key="item.label"
                class="border border-white/25 bg-white/[0.035] px-5 py-4"
            >
                <div class="mb-3 flex items-center justify-between gap-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="text-sm font-semibold text-white">
                                {{ item.label }}
                            </div>

                            <div class="text-xs text-white/35">
                                {{ item.meta }}
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-baseline gap-3">
                        <span
                            class="text-2xl font-semibold"
                            :style="{ color: deltaColor(item) }"
                        >
                            {{ item.current }}
                        </span>

                        <span
                            class="text-sm font-semibold"
                            :style="{ color: deltaColor(item) }"
                        >
                            {{ isUp(item) ? '+' : '' }}{{ deltaFor(item) }}
                        </span>
                    </div>
                </div>

                <div class="relative h-7 overflow-hidden rounded-md border border-white/30 bg-black/40">
                    <div
                        class="absolute inset-y-0 left-0 rounded-md border-r border-white/50"
                        :style="{
                            width: `${(item.current / maxValue) * 100}%`,
                            background: `linear-gradient(90deg, ${item.color} 0%, ${item.color} 68%, color-mix(in srgb, ${item.color} 78%, white 22%) 100%)`,
                        }"
                    />

                    <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.10)_1px,transparent_1px)] bg-[length:12.5%_100%]" />
                </div>
            </div>
        </div>
    </div>
</template>
