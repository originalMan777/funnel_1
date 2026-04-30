<script setup lang="ts">
type FlowStep = {
    label: string;
    value: number;
    color: string;
    meta?: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: FlowStep[];
    }>(),
    {
        title: 'LEAD FLOW',
        subtitle: 'Movement through funnel stages',
        data: () => [
            { label: 'Visitor', value: 100, color: '#2563eb', meta: 'Entry traffic' },
            { label: 'Engaged', value: 72, color: '#7c3aed', meta: 'Interacted' },
            { label: 'Captured', value: 38, color: '#16a34a', meta: 'Submitted lead' },
            { label: 'Qualified', value: 21, color: '#dc2626', meta: 'High intent' },
        ],
    },
);

const maxValue = Math.max(...props.data.map((item) => item.value), 100);
</script>

<template>
    <div class="w-full max-w-6xl mx-auto rounded-2xl border border-white/10 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-10 shadow-2xl shadow-black/40">
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
                FLOW
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div
                v-for="(step, index) in data"
                :key="step.label"
                class="relative overflow-hidden rounded-xl border border-white/20 bg-white/[0.04] p-5"
            >
                <div
                    class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full blur-3xl"
                    :style="{ background: step.color + '33' }"
                />

                <div class="relative">
                    <div class="mb-5 flex items-start justify-between">
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-[0.22em] text-white/40">
                                Step {{ index + 1 }}
                            </div>

                            <div class="mt-2 text-lg font-semibold text-white">
                                {{ step.label }}
                            </div>

                            <div class="mt-1 text-xs text-white/35">
                                {{ step.meta }}
                            </div>
                        </div>

                        <div
                            class="text-3xl font-semibold"
                            :style="{ color: step.color }"
                        >
                            {{ step.value }}
                        </div>
                    </div>

                    <div class="h-3 overflow-hidden rounded-full border border-white/20 bg-black/40">
                        <div
                            class="h-full rounded-full"
                            :style="{
                                width: `${(step.value / maxValue) * 100}%`,
                                background: `linear-gradient(90deg, ${step.color} 0%, ${step.color} 68%, color-mix(in srgb, ${step.color} 78%, white 22%) 100%)`,
                            }"
                        />
                    </div>

                    <div
                        v-if="index < data.length - 1"
                        class="mt-5 flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/35"
                    >
                        <span class="h-px flex-1 bg-white/20"></span>
                        <span>moves to</span>
                        <span class="h-px flex-1 bg-white/20"></span>
                    </div>

                    <div
                        v-else
                        class="mt-5 text-[10px] font-semibold uppercase tracking-[0.18em] text-green-300/70"
                    >
                        final stage
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
