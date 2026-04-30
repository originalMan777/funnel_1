<script setup lang="ts">
import { computed } from 'vue';

type PreviewMetric = {
    key?: string | null;
    label: string;
};

type PreviewSegment = {
    label: string;
    value: number;
    colorClass: string;
};

const props = defineProps<{
    metric: PreviewMetric;
}>();

const neutralSegmentPresets = {
    attribution_coverage: {
        centerLabel: 'Coverage',
        centerValue: '68%',
        segments: [
            { label: 'Attributed', value: 68, colorClass: 'text-sky-500' },
            { label: 'Unattributed', value: 20, colorClass: 'text-amber-400' },
            { label: 'Pending', value: 12, colorClass: 'text-slate-300' },
        ],
    },
    source_share: {
        centerLabel: 'Share',
        centerValue: '100%',
        segments: [
            { label: 'Direct', value: 44, colorClass: 'text-sky-500' },
            { label: 'Organic', value: 31, colorClass: 'text-emerald-500' },
            { label: 'Referral', value: 25, colorClass: 'text-amber-400' },
        ],
    },
    conversion_mix: {
        centerLabel: 'Mix',
        centerValue: '100%',
        segments: [
            { label: 'Consultation', value: 46, colorClass: 'text-sky-500' },
            { label: 'Guide', value: 29, colorClass: 'text-violet-500' },
            { label: 'Valuation', value: 25, colorClass: 'text-emerald-500' },
        ],
    },
    fallback: {
        centerLabel: 'Preview',
        centerValue: '100%',
        segments: [
            { label: 'Segment A', value: 42, colorClass: 'text-sky-500' },
            { label: 'Segment B', value: 33, colorClass: 'text-emerald-500' },
            { label: 'Segment C', value: 25, colorClass: 'text-slate-400' },
        ],
    },
};

const presetKey = computed(() => {
    const fingerprint = `${props.metric.key ?? ''} ${props.metric.label}`.toLowerCase();

    if (fingerprint.includes('attribution_coverage') || fingerprint.includes('attribution coverage')) {
        return 'attribution_coverage';
    }

    if (fingerprint.includes('source') || fingerprint.includes('share')) {
        return 'source_share';
    }

    if (fingerprint.includes('mix') || fingerprint.includes('breakdown')) {
        return 'conversion_mix';
    }

    return 'fallback';
});

const preset = computed(() => neutralSegmentPresets[presetKey.value]);

const segments = computed<PreviewSegment[]>(() =>
    preset.value.segments.map((segment) => ({ ...segment })),
);

const total = computed(() =>
    segments.value.reduce((sum, segment) => sum + segment.value, 0),
);

const radius = 34;
const circumference = 2 * Math.PI * radius;

const donutSegments = computed(() => {
    let offset = 0;

    return segments.value.map((segment) => {
        const normalizedValue = total.value > 0 ? segment.value / total.value : 0;
        const segmentLength = normalizedValue * circumference;
        const dashArray = `${segmentLength} ${circumference - segmentLength}`;
        const dashOffset = -offset;

        offset += segmentLength;

        return {
            ...segment,
            percent: total.value > 0 ? Math.round((segment.value / total.value) * 100) : 0,
            dashArray,
            dashOffset,
        };
    });
});
</script>

<template>
    <div class="grid gap-5 md:grid-cols-[auto,1fr] md:items-center">
        <div class="relative mx-auto h-36 w-36 shrink-0">
            <div class="absolute inset-1 rounded-full bg-sky-400/25 opacity-60 blur-xl"></div>
            <div class="absolute inset-4 rounded-full bg-white shadow-[0_18px_40px_rgba(15,23,42,0.14)]"></div>

            <svg viewBox="0 0 96 96" class="relative h-full w-full -rotate-90 drop-shadow-sm">
                <circle
                    cx="48"
                    cy="48"
                    :r="radius"
                    class="fill-none stroke-slate-200/80"
                    stroke-width="16"
                />

                <circle
                    v-for="segment in donutSegments"
                    :key="segment.label"
                    cx="48"
                    cy="48"
                    :r="radius"
                    class="fill-none stroke-current drop-shadow-md transition-all duration-500 ease-out"
                    :class="segment.colorClass"
                    stroke-linecap="round"
                    stroke-width="16"
                    :stroke-dasharray="segment.dashArray"
                    :stroke-dashoffset="segment.dashOffset"
                />
            </svg>

            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <div class="text-[10px] font-bold tracking-[0.2em] text-slate-400 uppercase">
                    {{ preset.centerLabel }}
                </div>

                <div class="mt-1 text-2xl font-black tracking-tight text-slate-950">
                    {{ preset.centerValue }}
                </div>

                <div class="mt-1 text-[11px] font-medium text-slate-400">
                    preview
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <div
                v-for="segment in donutSegments"
                :key="segment.label"
                class="flex items-center justify-between rounded-xl border border-slate-200/70 bg-white/95 px-3 py-2 shadow-sm transition hover:border-slate-300 hover:shadow-md"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="h-3 w-3 rounded-full shadow-sm"
                        :class="segment.colorClass.replace('text-', 'bg-')"
                    />

                    <span class="text-sm font-semibold text-slate-700">
                        {{ segment.label }}
                    </span>
                </div>

                <span class="text-sm font-bold text-slate-950">
                    {{ segment.percent }}%
                </span>
            </div>
        </div>
    </div>
</template>
