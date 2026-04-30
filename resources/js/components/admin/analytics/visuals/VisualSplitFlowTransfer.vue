<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        fromLabel?: string;
        toLabel?: string;
        startValue?: number;
        continueValue?: number;
        dropValue?: number;
    }>(),
    {
        title: 'FLOW TRANSFER',
        subtitle: 'Continue vs drop-off movement',
        fromLabel: 'Engaged',
        toLabel: 'Captured',
        startValue: 100,
        continueValue: 62,
        dropValue: 38,
    },
);
</script>

<template>
    <div class="w-full max-w-5xl mx-auto rounded-2xl border border-white/10 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-10 shadow-2xl shadow-black/40">
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

        <div class="rounded-2xl border border-white/15 bg-white/[0.04] p-6">
            <div class="grid items-center gap-6 lg:grid-cols-[1fr_1.2fr_1fr]">
                <!-- FROM -->
                <div class="rounded-xl border border-white/20 bg-black/25 p-5">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.24em] text-white/35">
                        From
                    </div>

                    <div class="mt-2 text-xl font-semibold text-white">
                        {{ fromLabel }}
                    </div>

                    <div class="mt-5 text-5xl font-semibold text-blue-300">
                        {{ startValue }}
                    </div>
                </div>

                <!-- TRANSFER -->
                <div class="relative min-h-[13rem] rounded-xl border border-white/20 bg-black/25 p-5">
                    <div class="absolute left-5 right-5 top-1/2 h-3 -translate-y-1/2 overflow-hidden rounded-full border border-white/20 bg-black/40">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-blue-700 via-blue-500 to-cyan-300"
                            :style="{ width: `${continueValue}%` }"
                        />
                    </div>

                    <div class="absolute left-5 right-5 top-[38%] h-px bg-white/15" />
                    <div class="absolute left-5 right-5 top-[62%] h-px bg-white/15" />

                    <div class="absolute left-5 top-5">
                        <div class="text-[10px] font-semibold uppercase tracking-[0.22em] text-green-300/70">
                            Continues
                        </div>

                        <div class="mt-1 text-3xl font-semibold text-green-300">
                            {{ continueValue }}%
                        </div>
                    </div>

                    <div class="absolute bottom-5 left-5">
                        <div class="text-[10px] font-semibold uppercase tracking-[0.22em] text-red-300/70">
                            Drops
                        </div>

                        <div class="mt-1 text-3xl font-semibold text-red-300">
                            {{ dropValue }}%
                        </div>
                    </div>

                    <div class="absolute right-5 top-1/2 -translate-y-1/2 text-4xl font-light text-white/35">
                        →
                    </div>
                </div>

                <!-- TO -->
                <div class="rounded-xl border border-white/20 bg-black/25 p-5">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.24em] text-white/35">
                        To
                    </div>

                    <div class="mt-2 text-xl font-semibold text-white">
                        {{ toLabel }}
                    </div>

                    <div class="mt-5 text-5xl font-semibold text-green-300">
                        {{ continueValue }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
