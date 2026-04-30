<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        label?: string;
        value?: string | number;
        meta?: string;
        delta?: string;
        deltaDirection?: 'up' | 'down' | 'neutral';
    }>(),
    {
        label: 'Total Leads',
        value: '1,284',
        meta: 'Current captured volume',
        delta: '+18',
        deltaDirection: 'up',
    },
);

const signalClass =
    props.deltaDirection === 'down'
        ? 'border-red-300/45 bg-red-400/10 text-red-300'
        : props.deltaDirection === 'neutral'
            ? 'border-yellow-300/45 bg-yellow-400/10 text-yellow-300'
            : 'border-green-300/45 bg-green-400/10 text-green-300';

const numberClass =
    props.deltaDirection === 'down'
        ? 'text-red-300'
        : props.deltaDirection === 'neutral'
            ? 'text-yellow-300'
            : 'text-green-300';
</script>

<template>
    <div class="w-full max-w-[23rem] rounded-2xl border border-white/12 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-6 shadow-2xl shadow-black/40">
        <div class="relative min-h-[19rem] overflow-hidden rounded-xl border border-white/15 bg-white/[0.04] p-5">
            <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-blue-500/20 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-20 -left-20 h-48 w-48 rounded-full bg-cyan-400/10 blur-3xl" />

            <div class="relative">
                <div class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">
                    {{ label }}
                </div>

                <div class="mt-2 text-xs text-white/35">
                    {{ meta }}
                </div>
            </div>

            <div class="relative mt-8 flex justify-center">
                <div class="relative flex aspect-square w-[15.5rem] items-center justify-center rounded-2xl border border-white/20 bg-gradient-to-br from-white/[0.09] via-white/[0.035] to-black/30 shadow-inner shadow-white/5">
                    <div
                        class="absolute right-3 top-3 rounded-md border px-2.5 py-1 text-sm font-semibold"
                        :class="signalClass"
                    >
                        {{ delta }}
                    </div>

                    <div
                        class="text-[4.9rem] font-semibold leading-none tracking-tight drop-shadow"
                        :class="numberClass"
                    >
                        {{ value }}
                    </div>
                </div>
            </div>

            <div class="relative mt-6">
                <div class="h-2 w-full overflow-hidden rounded-full border border-white/15 bg-black/35">
                    <div class="h-full w-[72%] rounded-full bg-gradient-to-r from-blue-800 via-blue-500 to-cyan-300" />
                </div>
            </div>
        </div>
    </div>
</template>
