<script setup lang="ts">
type BarItem = {
    label: string;
    value: number;
    color: string;
    meta?: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: BarItem[];
    }>(),
    {
        title: 'SOURCE COMPARISON',
        subtitle: 'Hard-edge ranked performance',
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
    <div class="w-full max-w-5xl mx-auto border border-white/10 bg-[#020617] p-10">
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

        <div class="space-y-3">
            <div
                v-for="item in data"
                :key="item.label"
                class="border border-white/30 bg-white/[0.035] px-4 py-3"
            >
                <div class="mb-2 flex items-center justify-between gap-6">
                    <div class="flex min-w-0 items-center gap-2">
                        <div class="text-sm font-semibold text-white">
                            {{ item.label }}
                        </div>

                        <div class="text-xs text-white/35">
                            — {{ item.meta }}
                        </div>
                    </div>

                    <div
                        class="shrink-0 text-lg font-semibold"
                        :style="{ color: item.color }"
                    >
                        {{ item.value }}%
                    </div>
                </div>

                <div class="relative h-6 overflow-hidden rounded-md border border-white/25 bg-black/35">
                    <div
                        class="absolute inset-y-0 left-0 rounded-md border-r border-white/50"
                        :style="{
    width: `${(item.value / maxValue) * 100}%`,
    background: `linear-gradient(90deg, ${item.color} 0%, ${item.color} 68%, color-mix(in srgb, ${item.color} 78%, white 22%) 100%)`,
}"
                    />

                    <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.12)_1px,transparent_1px)] bg-[length:12.5%_100%]" />
                </div>
            </div>
        </div>
    </div>
</template>
