<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
        value?: string | number;
        meta?: string;
        foot?: string;
    }>(),
    {
        label: 'Total Leads',
        value: '0',
        meta: 'Current captured volume',
        foot: 'Raw number display',
    },
);
</script>

<template>
    <div class="mt-8 rounded-[1.6rem] border border-slate-700 bg-[linear-gradient(145deg,#1e293b,#0f172a)] p-5 shadow-[0_28px_70px_rgba(2,6,23,0.55)]">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">
                    {{ label }}
                </p>
                <p class="mt-1 text-xs font-medium text-slate-500">
                    {{ meta }}
                </p>
            </div>

            <div class="h-3 w-3 rounded-full bg-cyan-300 shadow-[0_0_18px_rgba(103,232,249,0.85)]"></div>
        </div>

        <div class="rounded-[1.25rem] border border-cyan-200/20 bg-slate-900 p-4 shadow-[inset_0_2px_10px_rgba(255,255,255,0.05)]">
            <div class="relative overflow-hidden rounded-[1rem] border border-white/10 bg-[radial-gradient(circle_at_30%_10%,rgba(255,255,255,0.28),transparent_18%),linear-gradient(135deg,#0ea5e9,#2563eb_55%,#111827)] px-5 py-10 shadow-[inset_0_0_40px_rgba(255,255,255,0.08)]">
                <div class="absolute left-0 top-0 h-px w-full bg-white/40"></div>
                <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-cyan-200/20 blur-2xl"></div>

                <p class="text-center text-[11px] font-bold uppercase tracking-[0.28em] text-cyan-100/80">
                    Display Value
                </p>

                <p class="mt-4 text-center text-7xl font-black tracking-[-0.08em] text-white drop-shadow-[0_10px_24px_rgba(15,23,42,0.45)]">
                    {{ value }}
                </p>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500">
                    Type
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-200">
                    {{ foot }}
                </p>
            </div>

            <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500">
                    Meaning
                </p>
                <p class="mt-1 text-sm font-semibold text-slate-200">
                    No judgment
                </p>
            </div>
        </div>
    </div>
</template>
