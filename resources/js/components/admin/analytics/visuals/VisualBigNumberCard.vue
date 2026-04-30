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

const displayValue = computed(() => {
    const raw = props.value ?? props.parsedData?.displayValue ?? props.parsedData?.numericValue;

    if (raw === null || raw === undefined || raw === '') {
        return '—';
    }

    return String(raw);
});

const isPercent = computed(() => displayValue.value.includes('%'));

const eyebrow = computed(() => (isPercent.value ? 'Percentage' : 'Value'));

/**
 * SCALE — make it BIGGER than before
 */
const sizeClasses = computed(() => {
    if (props.size === 'sm') {
        return {
            outer: 'p-4',
            tile: 'p-4',
            value: 'text-5xl',
        };
    }

    if (props.size === 'lg') {
        return {
            outer: 'p-8',
            tile: 'p-10',
            value: 'text-[5rem] sm:text-[6.5rem] lg:text-[7.5rem]',
        };
    }

    return {
        outer: 'p-6',
        tile: 'p-8',
        value: 'text-[4rem] sm:text-[5rem]',
    };
});
</script>

<template>
    <!-- OUTER CARD -->
    <div
        class="flex h-full flex-col justify-between rounded-[1.5rem] border border-slate-200 bg-white shadow-sm"
        :class="sizeClasses.outer"
    >
        <!-- META (TOP - LIGHT, NON-COMPETING) -->
        <div class="flex items-center justify-between">
            <p class="text-[10px] font-semibold uppercase tracking-[0.26em] text-slate-400">
                {{ eyebrow }}
            </p>

            <span class="text-[10px] font-medium text-slate-400">
                Standalone
            </span>
        </div>

        <!-- TILE (THIS IS THE FOCUS) -->
        <div
            class="flex flex-1 items-center justify-center rounded-[1.25rem] bg-slate-50"
            :class="sizeClasses.tile"
        >
            <p
                class="text-center font-semibold leading-none tracking-[-0.06em] text-slate-950"
                :class="sizeClasses.value"
            >
                {{ displayValue }}
            </p>
        </div>

        <!-- FOOTER (LOW PRIORITY) -->
        <div class="mt-6 flex items-center justify-between">
            <p class="text-xs text-slate-400">
                No judgment
            </p>

            <div class="h-[2px] w-10 bg-slate-200"></div>
        </div>
    </div>
</template>
