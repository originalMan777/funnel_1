<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
        value?: string | number;
        meta?: string;
    }>(),
    {
        label: 'Signal Strength',
        value: '0',
        meta: 'Dot travels across the arc',
    },
);
</script>

<template>
    <div class="mt-8 rounded-[1.6rem] border border-violet-300/20 bg-[linear-gradient(145deg,#111827,#020617)] p-6 shadow-[0_30px_80px_rgba(0,0,0,0.68)]">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-violet-200/70">
                    {{ label }}
                </p>
                <p class="mt-1 text-xs font-medium text-slate-500">
                    {{ meta }}
                </p>
            </div>

            <p class="text-4xl font-black tracking-[-0.06em] text-white">
                {{ value }}
            </p>
        </div>

        <div class="rounded-[1.25rem] border border-white/10 bg-slate-900/80 p-5">
            <svg viewBox="0 0 320 210" class="h-64 w-full overflow-visible">
                <defs>
                    <linearGradient id="arc-strength-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#ef4444" />
                        <stop offset="50%" stop-color="#f59e0b" />
                        <stop offset="100%" stop-color="#22c55e" />
                    </linearGradient>
                    <filter id="arc-dot-glow" x="-80%" y="-80%" width="260%" height="260%">
                        <feDropShadow dx="0" dy="0" stdDeviation="10" flood-color="#22c55e" flood-opacity="0.75" />
                    </filter>
                </defs>

                <path
                    d="M 42 154 A 118 118 0 0 1 278 154"
                    fill="none"
                    stroke="rgba(148,163,184,0.18)"
                    stroke-linecap="round"
                    stroke-width="26"
                />

                <path
                    d="M 42 154 A 118 118 0 0 1 278 154"
                    fill="none"
                    stroke="url(#arc-strength-gradient)"
                    stroke-linecap="round"
                    stroke-width="26"
                />

                <circle
                    cx="225"
                    cy="62"
                    r="17"
                    fill="#020617"
                    stroke="#22c55e"
                    stroke-width="8"
                    filter="url(#arc-dot-glow)"
                />

                <circle cx="225" cy="62" r="6" fill="#bbf7d0" />

                <text x="42" y="190" text-anchor="middle" class="fill-rose-300 text-[13px] font-bold uppercase tracking-[0.22em]">
                    Weak
                </text>

                <text x="278" y="190" text-anchor="middle" class="fill-emerald-300 text-[13px] font-bold uppercase tracking-[0.22em]">
                    Strong
                </text>

                <text x="160" y="142" text-anchor="middle" class="fill-slate-400 text-[11px] font-bold uppercase tracking-[0.24em]">
                    Indicator shifts color
                </text>
            </svg>
        </div>
    </div>
</template>
