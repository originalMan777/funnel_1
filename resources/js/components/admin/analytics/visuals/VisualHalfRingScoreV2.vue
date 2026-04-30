<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    value?: number | string | null;
    parsedData?: {
        numericValue?: number | null;
        displayValue?: string | number | null;
    } | null;
    status?: string | null;
    size?: string | null;
}>();

const gradientId = `half-ring-score-v2-${Math.random().toString(36).slice(2)}`;
const arcLength = 240;

const parseNumericValue = (value: string | number | null | undefined) => {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : null;
    }

    const normalized = String(value ?? '')
        .replace(/,/g, '')
        .replace('%', '')
        .trim();

    if (!normalized || normalized === '-' || normalized === '—') {
        return null;
    }

    const parsed = Number(normalized);

    return Number.isFinite(parsed) ? parsed : null;
};

const numericValue = computed(() => {
    const fromValue = parseNumericValue(props.value);

    if (fromValue !== null) {
        return fromValue;
    }

    return parseNumericValue(props.parsedData?.numericValue);
});

const clampedValue = computed(() => {
    if (numericValue.value === null) {
        return null;
    }

    return Math.min(Math.max(numericValue.value, 0), 100);
});

const percent = computed(() => clampedValue.value ?? 0);

const displayValue = computed(() => {
    if (clampedValue.value === null) {
        return '—';
    }

    const rounded = Number(clampedValue.value.toFixed(clampedValue.value % 1 === 0 ? 0 : 1));

    return `${rounded}%`;
});

const scoreLabel = computed(() => {
    if (clampedValue.value === null) {
        return 'Score unavailable';
    }

    if (clampedValue.value < 35) return 'Weak';
    if (clampedValue.value < 70) return 'Moderate';
    return 'Strong';
});

const activeDasharray = computed(() => {
    const filled = Number(((percent.value / 100) * arcLength).toFixed(1));

    return `${filled} ${arcLength}`;
});

const markerPoint = computed(() => {
    const position = percent.value / 100;
    const angle = Math.PI * (1 - position);
    const radius = 82;

    return {
        x: 100 + radius * Math.cos(angle),
        y: 104 - radius * Math.sin(angle),
    };
});
</script>

<template>
    <div class="flex h-full flex-col justify-between rounded-[1.5rem] border border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] p-5 shadow-[0_18px_45px_-32px_rgba(15,23,42,0.65)]">
        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">
            <span>Weak</span>
            <span>Moderate</span>
            <span>Strong</span>
        </div>

        <div class="relative mt-4 flex flex-1 items-center justify-center">
            <svg
                viewBox="0 0 200 132"
                class="w-full max-w-[17rem] overflow-visible"
                aria-hidden="true"
            >
                <defs>
                    <linearGradient :id="gradientId" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#f97316" />
                        <stop offset="50%" stop-color="#0ea5e9" />
                        <stop offset="100%" stop-color="#10b981" />
                    </linearGradient>
                </defs>

                <path
                    d="M 18 104 A 82 82 0 0 1 182 104"
                    fill="none"
                    stroke="#e2e8f0"
                    stroke-linecap="round"
                    stroke-width="18"
                />
                <path
                    d="M 18 104 A 82 82 0 0 1 182 104"
                    fill="none"
                    :stroke="`url(#${gradientId})`"
                    stroke-linecap="round"
                    stroke-width="18"
                    :stroke-dasharray="activeDasharray"
                />
                <circle
                    :cx="markerPoint.x"
                    :cy="markerPoint.y"
                    r="11"
                    fill="#ffffff"
                    stroke="#0f172a"
                    stroke-width="6"
                />
                <text
                    x="100"
                    y="84"
                    text-anchor="middle"
                    class="fill-slate-950 text-[30px] font-black"
                >
                    {{ displayValue }}
                </text>
            </svg>
        </div>

        <div class="mt-3 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                {{ scoreLabel }}
            </p>
        </div>
    </div>
</template>
