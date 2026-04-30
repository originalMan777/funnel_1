<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        label?: string;
        value?: number;
        meta?: string;
    }>(),
    {
        label: 'Net Change',
        value: 24,
        meta: 'Negative / zero / positive signal',
    },
);

const signalColor = props.value < 0 ? '#f87171' : props.value > 0 ? '#86efac' : '#fde047';
const displayValue = props.value > 0 ? `+${props.value}` : `${props.value}`;
const markerPosition = Math.min(Math.max(((props.value + 100) / 200) * 100, 0), 100);

const markerCapColor =
    props.value < -50
        ? '#ef4444'
        : props.value < 0
            ? '#fb923c'
            : props.value === 0
                ? '#fde047'
                : props.value < 50
                    ? '#a3e635'
                    : '#22c55e';
</script>

<template>
    <div class="w-full max-w-[26rem] mx-auto rounded-2xl border border-white/10 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-6 shadow-2xl shadow-black/40">
        <div class="relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.04] p-5">
            <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-green-400/10 blur-3xl" />
            <div class="pointer-events-none absolute -left-16 -bottom-16 h-40 w-40 rounded-full bg-red-400/10 blur-3xl" />

            <div class="relative">
                <div class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">
                    {{ label }}
                </div>

                <div class="mt-2 text-xs text-white/35">
                    {{ meta }}
                </div>
            </div>

            <div class="relative mt-8 flex justify-center">
                <div
                    class="text-[5.5rem] font-semibold leading-none tracking-tight drop-shadow"
                    :style="{ color: signalColor }"
                >
                    {{ displayValue }}
                </div>
            </div>

            <div class="relative mt-8">
                <div class="relative h-5 overflow-visible rounded-full border border-white/20 bg-black/40">
                    <div class="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r from-red-500 via-red-400 to-yellow-300" />
                    <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-r from-yellow-300 via-green-400 to-green-500" />

                    <div class="absolute inset-y-0 left-1/2 w-px bg-white/70" />

                    <!-- PROTRUDING MARKER -->
                    <div
                        class="absolute top-1/2 flex -translate-y-1/2 flex-col items-center"
                        :style="{ left: `calc(${markerPosition}% - 0.5rem)` }"
                    >
                        <div
                            class="h-2 w-4 rounded-t-sm shadow-md"
                            :style="{ background: markerCapColor }"
                        ></div>

                        <div class="h-10 w-2 border border-white bg-white shadow-xl"></div>

                        <div
                            class="h-2 w-4 rounded-b-sm shadow-md"
                            :style="{ background: markerCapColor }"
                        ></div>
                    </div>
                </div>

                <div class="mt-3 flex justify-between text-[10px] font-semibold uppercase tracking-[0.18em] text-white/35">
                    <span>Negative</span>
                    <span>0</span>
                    <span>Positive</span>
                </div>
            </div>
        </div>
    </div>
</template>
