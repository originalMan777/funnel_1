<script setup lang="ts">
type RangeItem = {
    label: string;
    min: number;
    max: number;
    value: number;
    color: string;
    meta?: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: RangeItem[];
    }>(),
    {
        title: 'RANGE VARIANCE',
        subtitle: 'Position within performance range',
        data: () => [
            { label: 'Google', min: 20, max: 90, value: 72, color: '#2563eb', meta: 'Strong range' },
            { label: 'Facebook', min: 10, max: 80, value: 55, color: '#7c3aed', meta: 'Mid performance' },
            { label: 'Direct', min: 30, max: 85, value: 48, color: '#16a34a', meta: 'Stable baseline' },
            { label: 'Referral', min: 5, max: 60, value: 25, color: '#dc2626', meta: 'Low band' },
        ],
    },
);
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
                RANGE
            </div>
        </div>

        <div class="space-y-4">
            <div
                v-for="item in data"
                :key="item.label"
                class="border border-white/25 bg-white/[0.035] px-5 py-4"
            >
                <!-- LABEL ROW -->
                <div class="mb-2 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-white">
                            {{ item.label }}
                        </div>

                        <div class="text-xs text-white/35">
                            {{ item.meta }}
                        </div>
                    </div>

                    <div
                        class="text-lg font-semibold"
                        :style="{ color: item.color }"
                    >
                        {{ item.value }}
                    </div>
                </div>

                <!-- RANGE BAR -->
                <div class="relative h-6 rounded-md border border-white/25 bg-black/40">
                    
                    <!-- RANGE TRACK -->
                    <div
                        class="absolute inset-y-0 left-0 rounded-md border-r border-white/50"
                        :style="{
                            left: `${item.min}%`,
                            width: `${item.max - item.min}%`,
                            background: `linear-gradient(90deg, ${item.color} 0%, ${item.color} 70%, color-mix(in srgb, ${item.color} 80%, white 20%) 100%)`,
                        }"
                    />

                    <!-- CURRENT VALUE MARKER -->
                    <div
                        class="absolute top-1/2 h-4 w-4 -translate-y-1/2 border border-white bg-white"
                        :style="{ left: `${item.value}%` }"
                    />

                </div>

                <!-- SCALE LABELS -->
                <div class="mt-2 flex justify-between text-[10px] text-white/30">
                    <span>{{ item.min }}</span>
                    <span>{{ item.max }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
