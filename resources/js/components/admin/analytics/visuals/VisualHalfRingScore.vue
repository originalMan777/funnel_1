<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    value: [String, Number],
    parsedData: Object,
    status: String,
    size: String,
});

/**
 * Convert value → number
 */
const numericValue = computed(() => {
    const raw = props.value ?? props.parsedData?.numericValue;

    if (raw === null || raw === undefined) return null;

    const parsed = Number(String(raw).replace('%', ''));
    return Number.isFinite(parsed) ? parsed : null;
});

/**
 * Normalize 0 → 100 into 0 → 1
 */
const position = computed(() => {
    if (numericValue.value === null) return 0.5;

    return Math.min(Math.max(numericValue.value / 100, 0), 1);
});

/**
 * Convert position into angle (half circle)
 */
const angle = computed(() => {
    return -90 + (position.value * 180);
});
</script>

<template>
    <div class="flex h-full flex-col justify-between rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">

        <!-- LABEL -->
        <div class="flex justify-between text-[10px] uppercase tracking-[0.24em] text-slate-400">
            <span>Weak</span>
            <span>Strong</span>
        </div>

        <!-- ARC -->
        <div class="relative flex flex-1 items-center justify-center">
            <svg viewBox="0 0 200 120" class="w-full max-w-[260px]">
                
                <!-- Base arc -->
                <path
                    d="M 10 100 A 90 90 0 0 1 190 100"
                    fill="none"
                    stroke="#e2e8f0"
                    stroke-width="10"
                    stroke-linecap="round"
                />

                <!-- Marker -->
                <g
                    :transform="`rotate(${angle} 100 100)`"
                >
                    <circle
                        cx="100"
                        cy="10"
                        r="6"
                        fill="#0f172a"
                    />
                </g>
            </svg>
        </div>

        <!-- VALUE -->
        <div class="text-center">
            <p class="text-3xl font-semibold text-slate-900">
                {{ numericValue !== null ? numericValue : '—' }}
            </p>
        </div>

    </div>
</template>
