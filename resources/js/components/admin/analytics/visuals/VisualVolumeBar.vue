<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value?: string | number | null;
        parsedData?: Record<string, unknown> | null;
        status?: string | null;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        value: null,
        parsedData: null,
        status: null,
        size: 'md',
    },
);

const parseNumber = (value: unknown): number | null => {
    if (typeof value === 'number') return Number.isFinite(value) ? value : null;

    const parsed = Number(String(value ?? '').replace(/,/g, '').trim());
    return Number.isFinite(parsed) ? parsed : null;
};

const numericValue = computed(() => {
    return parseNumber(props.parsedData?.numericValue) ?? parseNumber(props.value);
});

const normalized = computed(() => {
    if (!numericValue.value || numericValue.value <= 0) return 0;

    return Math.min(Math.log10(numericValue.value + 1) / 5, 1);
});

const layers = computed(() => {
    return Math.max(1, Math.floor(normalized.value * 6));
});

const widthPercent = computed(() => {
    return Math.max(20, normalized.value * 100);
});

const formattedValue = computed(() => {
    if (!numericValue.value) return '0';
    return new Intl.NumberFormat().format(numericValue.value);
});
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <!-- VALUE -->
        <div class="mb-4">
            <p class="text-xs uppercase tracking-wider text-slate-500">
                Volume
            </p>
            <p class="text-4xl font-semibold text-slate-900">
                {{ formattedValue }}
            </p>
        </div>

        <!-- MASS BLOCK -->
        <div class="space-y-1">
            <div
                v-for="i in layers"
                :key="i"
                class="h-3 rounded-full bg-gradient-to-r from-blue-500 to-blue-400 transition-all duration-500"
                :style="{ width: widthPercent + '%' }"
            />
        </div>

        <!-- SUBTEXT -->
        <p class="mt-4 text-xs text-slate-400">
            Activity volume (scaled)
        </p>
    </div>
</template>
