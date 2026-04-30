<script setup lang="ts">
type Segment = {
    label: string;
    value: number;
    color: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        data?: Segment[];
    }>(),
    {
        title: 'TRAFFIC SOURCES',
        subtitle: 'High contrast distribution',
        data: () => [
            { label: 'Search', value: 50, color: '#1e3a8a' },
            { label: 'Social', value: 30, color: '#6d28d9' },
            { label: 'Direct', value: 20, color: '#166534' },
        ],
    },
);

const center = 100;
const outerRadius = 78;
const innerRadius = 54;
const gapDeg = 3;

function polarToCartesian(cx: number, cy: number, r: number, angle: number) {
    const rad = (angle - 90) * (Math.PI / 180);

    return {
        x: cx + r * Math.cos(rad),
        y: cy + r * Math.sin(rad),
    };
}

function describeArc(startAngle: number, endAngle: number) {
    const startOuter = polarToCartesian(center, center, outerRadius, endAngle);
    const endOuter = polarToCartesian(center, center, outerRadius, startAngle);

    const startInner = polarToCartesian(center, center, innerRadius, startAngle);
    const endInner = polarToCartesian(center, center, innerRadius, endAngle);

    const largeArcFlag = endAngle - startAngle <= 180 ? 0 : 1;

    return `
        M ${startOuter.x} ${startOuter.y}
        A ${outerRadius} ${outerRadius} 0 ${largeArcFlag} 0 ${endOuter.x} ${endOuter.y}
        L ${startInner.x} ${startInner.y}
        A ${innerRadius} ${innerRadius} 0 ${largeArcFlag} 1 ${endInner.x} ${endInner.y}
        Z
    `;
}

let currentAngle = 0;

const segments = props.data.map((seg) => {
    const angle = (seg.value / 100) * 360;

    const start = currentAngle + gapDeg / 2;
    const end = currentAngle + angle - gapDeg / 2;

    const path = describeArc(start, end);

    currentAngle += angle;

    return {
        ...seg,
        path,
    };
});
</script>

<template>
    <div class="w-full max-w-5xl mx-auto rounded-2xl border border-white/10 bg-[#020617] p-10">
        <div class="mb-8 flex items-start justify-between">
            <div>
                <div class="text-xs font-semibold tracking-[0.2em] text-white/60">
                    {{ title }}
                </div>

                <div class="mt-1 text-sm text-white/40">
                    {{ subtitle }}
                </div>
            </div>

            <div class="rounded-lg bg-white px-3 py-1 text-xs font-semibold text-slate-950">
                DISTRIBUTION
            </div>
        </div>

        <div class="flex items-center justify-between gap-10">
            <div class="relative flex h-[320px] w-[320px] items-center justify-center">
                <svg viewBox="0 0 200 200" class="h-full w-full">
                    <!-- THINNER WHITE BASE -->
                    <g>
                        <path
                            v-for="seg in segments"
                            :key="`${seg.label}-white`"
                            :d="seg.path"
                            fill="#ffffff"
                        />
                    </g>

                    <!-- COLOR LAYER: less inset = thinner white outline -->
                    <g transform="scale(0.94) translate(6.35 6.35)">
                        <path
                            v-for="seg in segments"
                            :key="seg.label"
                            :d="seg.path"
                            :fill="seg.color"
                        />
                    </g>
                </svg>

                <div class="absolute text-center">
                    <div class="mb-2 text-[10px] tracking-[0.3em] text-white/40">
                        TOTAL
                    </div>

                    <div class="text-4xl font-semibold text-white">
                        100%
                    </div>
                </div>
            </div>

            <div class="flex-1 space-y-4">
                <div
                    v-for="seg in data"
                    :key="seg.label"
                    class="rounded-xl border border-white/35 bg-white/5 px-4 py-3"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-2.5 w-2.5 rounded-sm"
                                :style="{ background: seg.color }"
                            />

                            <div class="text-sm font-medium text-white">
                                {{ seg.label }}
                            </div>
                        </div>

                        <div class="text-sm font-semibold text-white">
                            {{ seg.value }}%
                        </div>
                    </div>

                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                        <div
                            class="h-full rounded-full"
                            :style="{
                                width: `${seg.value}%`,
                                background: seg.color,
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
