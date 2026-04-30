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
        subtitle: 'Bold distribution visual',
        data: () => [
            { label: 'Google', value: 50, color: '#22c55e' },
            { label: 'Facebook', value: 30, color: '#f59e0b' },
            { label: 'Direct', value: 20, color: '#ef4444' },
        ],
    },
);

const center = 100;
const outerRadius = 78;
const innerRadius = 54;

// GAP IN DEGREES (controls spacing)
const gapDeg = 4;

// build arcs
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
    <div class="w-full max-w-5xl mx-auto rounded-2xl p-10 bg-gradient-to-br from-[#071026] to-[#020617] border border-white/5">

        <!-- HEADER -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <div class="text-xs tracking-[0.2em] text-cyan-400 font-semibold">
                    {{ title }}
                </div>
                <div class="text-sm text-white/60 mt-1">
                    {{ subtitle }}
                </div>
            </div>

            <div class="text-xs px-3 py-1 rounded-lg bg-white/5 border border-white/10 text-white/70">
                DISTRIBUTION
            </div>
        </div>

        <!-- BODY -->
        <div class="flex items-center justify-between gap-10">

            <!-- DONUT -->
            <div class="relative flex items-center justify-center w-[320px] h-[320px]">

                <svg viewBox="0 0 200 200" class="w-full h-full">
                    <g>
                        <path
                            v-for="seg in segments"
                            :key="seg.label"
                            :d="seg.path"
                            :fill="seg.color"
                        />
                    </g>
                </svg>

                <!-- CENTER -->
                <div class="absolute text-center">
                    <div class="text-[10px] tracking-[0.3em] text-white/40 mb-2">
                        TOTAL
                    </div>
                    <div class="text-4xl font-semibold text-white">
                        100%
                    </div>
                </div>
            </div>

            <!-- LEGEND -->
            <div class="flex-1 space-y-4">

                <div
                    v-for="seg in data"
                    :key="seg.label"
                    class="bg-white/[0.04] border border-white/5 rounded-xl px-4 py-3"
                >
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-2.5 h-2.5 rounded-sm"
                                :style="{ background: seg.color }"
                            ></div>
                            <div class="text-white/80 text-sm font-medium">
                                {{ seg.label }}
                            </div>
                        </div>

                        <div
                            class="text-sm font-semibold"
                            :style="{ color: seg.color }"
                        >
                            {{ seg.value }}%
                        </div>
                    </div>

                    <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full"
                            :style="{
                                width: seg.value + '%',
                                background: seg.color
                            }"
                        ></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
