<script setup lang="ts">
type FunnelStep = {
    label: string;
    value: number;
    color: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        steps?: FunnelStep[];
    }>(),
    {
        title: 'FUNNEL',
        subtitle: 'Tapered conversion flow',
        steps: () => [
            { label: 'Visitors', value: 1000, color: '#2563eb' },
            { label: 'Engaged', value: 720, color: '#7c3aed' },
            { label: 'Captured', value: 380, color: '#22c55e' },
            { label: 'Qualified', value: 210, color: '#f97316' },
        ],
    },
);

const viewWidth = 520;
const viewHeight = 300;
const topWidth = 440;
const bottomWidth = 120;
const rowGap = 28;      // space BETWEEN sections (this is what you want)
const rowHeight = 44;   // slightly thinner blocks so spacing stands out
const startY = 28;
const centerX = viewWidth / 2;

const leftAt = (y: number) => {
    const t = y / viewHeight;
    const width = topWidth - (topWidth - bottomWidth) * t;

    return centerX - width / 2;
};

const rightAt = (y: number) => {
    const t = y / viewHeight;
    const width = topWidth - (topWidth - bottomWidth) * t;

    return centerX + width / 2;
};

const stepPath = (index: number) => {
    const y1 = startY + index * (rowHeight + rowGap);
    const y2 = y1 + rowHeight;

    return `
        M ${leftAt(y1)} ${y1}
        L ${rightAt(y1)} ${y1}
        L ${rightAt(y2)} ${y2}
        L ${leftAt(y2)} ${y2}
        Z
    `;
};

const textY = (index: number) =>
    startY + index * (rowHeight + rowGap) + rowHeight / 2 + 5;
</script>

<template>
    <div class="w-full max-w-3xl mx-auto rounded-2xl border border-white/10 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-8 shadow-2xl shadow-black/40">
        <div class="relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.04] p-6">
            <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl" />
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-56 w-56 rounded-full bg-purple-400/10 blur-3xl" />

            <div class="mb-6">
                <div class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">
                    {{ title }}
                </div>

                <div class="mt-2 text-xs text-white/35">
                    {{ subtitle }}
                </div>
            </div>

            <div class="rounded-xl border border-white/10 bg-black/20 p-4">
                <svg
                    :viewBox="`0 0 ${viewWidth} ${viewHeight}`"
                    class="h-[300px] w-full overflow-visible"
                    preserveAspectRatio="xMidYMid meet"
                >
                    <path
                        v-for="(step, index) in steps"
                        :key="step.label"
                        :d="stepPath(index)"
                        :fill="`url(#funnelGradient-${index})`"
                        stroke="rgba(255,255,255,0.16)"
                        stroke-width="1"
                    />

                    <defs>
                        <linearGradient
                            v-for="(step, index) in steps"
                            :id="`funnelGradient-${index}`"
                            :key="`gradient-${step.label}`"
                            x1="0%"
                            y1="0%"
                            x2="100%"
                            y2="0%"
                        >
                            <stop offset="0%" :stop-color="step.color" stop-opacity="0.35" />
                            <stop offset="65%" :stop-color="step.color" stop-opacity="0.22" />
                            <stop offset="100%" :stop-color="step.color" stop-opacity="0.1" />
                        </linearGradient>
                    </defs>

                    <g
                        v-for="(step, index) in steps"
                        :key="`${step.label}-text`"
                    >
                        <text
                            :x="leftAt(startY + index * (rowHeight + rowGap)) + 22"
                            :y="textY(index)"
                            fill="white"
                            font-size="14"
                            font-weight="700"
                        >
                            {{ step.label }}
                        </text>

                        <text
                            :x="rightAt(startY + index * (rowHeight + rowGap)) - 22"
                            :y="textY(index)"
                            :fill="step.color"
                            font-size="18"
                            font-weight="800"
                            text-anchor="end"
                        >
                            {{ step.value }}
                        </text>
                    </g>
                </svg>
            </div>
        </div>
    </div>
</template>
