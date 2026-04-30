<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        label?: string;
        value?: number;
        goal?: number;
        meta?: string;
        color?: string;
    }>(),
    {
        label: 'Monthly Lead Goal',
        value: 128,
        goal: 200,
        meta: 'Current progress toward target',
        color: '#2563eb',
    },
);

const progress = Math.min(Math.round((props.value / props.goal) * 100), 100);
const remaining = Math.max(props.goal - props.value, 0);
</script>

<template>
    <div class="w-full max-w-[34rem] mx-auto rounded-2xl border border-white/10 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-6 shadow-2xl shadow-black/40">
        <div class="relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.04] p-6">
            <div class="pointer-events-none absolute -right-16 -top-16 h-44 w-44 rounded-full bg-blue-500/15 blur-3xl" />
            <div class="pointer-events-none absolute -left-16 -bottom-16 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl" />

            <div class="relative flex items-start justify-between gap-6">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">
                        {{ label }}
                    </div>

                    <div class="mt-2 text-xs text-white/35">
                        {{ meta }}
                    </div>
                </div>

                <div class="rounded-md border border-white/20 bg-black/25 px-3 py-1 text-xs font-semibold text-white/55">
                    GOAL
                </div>
            </div>

            <div class="relative mt-8 flex items-end justify-between gap-8">
                <div>
                    <div class="text-[4.8rem] font-semibold leading-none tracking-tight text-white">
                        {{ progress }}%
                    </div>

                    <div class="mt-3 text-sm text-white/40">
                        {{ value }} of {{ goal }} reached
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/35">
                        Remaining
                    </div>

                    <div
                        class="mt-2 text-3xl font-semibold"
                        :style="{ color }"
                    >
                        {{ remaining }}
                    </div>
                </div>
            </div>

            <div class="relative mt-8">
                <div class="relative h-8 overflow-hidden rounded-md border border-white/25 bg-black/40">
                    <div
                        class="absolute inset-y-0 left-0 rounded-md border-r border-white/50"
                        :style="{
                            width: `${progress}%`,
                            background: `linear-gradient(90deg, ${color} 0%, ${color} 68%, color-mix(in srgb, ${color} 78%, white 22%) 100%)`,
                        }"
                    />

                    <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.10)_1px,transparent_1px)] bg-[length:10%_100%]" />
                </div>

                <div class="mt-3 flex justify-between text-[10px] font-semibold uppercase tracking-[0.18em] text-white/35">
                    <span>0</span>
                    <span>Target</span>
                </div>
            </div>
        </div>
    </div>
</template>
