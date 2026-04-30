<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
        value?: string | number;
        meta?: string;
    }>(),
    {
        label: 'Pipeline Value',
        value: '0',
        meta: 'Qualified opportunity total',
    },
);
</script>

<template>
    <div class="mt-8 rounded-[1.6rem] border border-emerald-400/20 bg-black p-5 shadow-[0_30px_80px_rgba(0,0,0,0.72)]">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-emerald-300/70">
                    {{ label }}
                </p>
                <p class="mt-1 text-xs font-medium text-slate-500">
                    {{ meta }}
                </p>
            </div>

            <span class="rounded-md border border-emerald-400/25 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-200">
                live
            </span>
        </div>

        <div class="rounded-[1.25rem] border border-emerald-400/20 bg-[linear-gradient(180deg,#020617,#030712)] p-4 shadow-[inset_0_0_32px_rgba(16,185,129,0.08)]">
            <div class="rounded-[1rem] border border-emerald-300/20 bg-[radial-gradient(circle_at_50%_0%,rgba(52,211,153,0.22),transparent_24%),linear-gradient(180deg,#052e2b,#020617)] px-5 py-10">
                <div class="flex items-center justify-between">
                    <span class="h-1.5 w-10 rounded-full bg-emerald-300/80"></span>
                    <span class="h-1.5 w-10 rounded-full bg-emerald-300/30"></span>
                </div>

                <p class="mt-8 text-center text-7xl font-black tracking-[-0.09em] text-emerald-300 drop-shadow-[0_0_24px_rgba(52,211,153,0.42)]">
                    {{ value }}
                </p>

                <p class="mt-6 text-center text-[11px] font-bold uppercase tracking-[0.28em] text-emerald-100/60">
                    Console Output
                </p>
            </div>
        </div>

        <div class="mt-5 rounded-xl border border-emerald-400/15 bg-emerald-400/5 px-4 py-3">
            <div class="flex items-center justify-between gap-4">
                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-300/60">
                    Display Status
                </p>
                <p class="text-sm font-bold text-emerald-200">
                    Number only / no evaluation
                </p>
            </div>
        </div>
    </div>
</template>
