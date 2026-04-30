<script setup lang="ts">
import { computed } from 'vue';
import { getApprovedVisualDefinition } from '@/components/admin/analytics/visualizationRegistry';
import VisualBigNumberCard from '@/components/admin/analytics/visuals/VisualBigNumberCard.vue';

const props = defineProps({
    metric: Object,
    compact: Boolean,
});

/**
 * Normalize numeric value
 */
const numericValue = computed(() => {
    const raw = props.metric?.value;
    const parsed = Number(String(raw ?? '').replace(/,/g, ''));
    return Number.isFinite(parsed) ? parsed : null;
});

/**
 * Format value for display
 */
const formattedValue = computed(() => {
    if (numericValue.value === null) return '—';
    return new Intl.NumberFormat().format(numericValue.value);
});

/**
 * Resolve visual from registry (SAFE)
 */
const approvedVisual = computed(() => {
    const visual = getApprovedVisualDefinition(props.metric?.approvedVisualKey ?? null);

    return visual?.status !== 'hidden' ? visual : null;
});
</script>

<template>
    <div class="p-6">
        <!-- DEBUG -->
        <div class="text-xs text-slate-400 mb-2">
            Visual: {{ props.metric?.approvedVisualKey || 'none' }}
        </div>

        <!-- PRIMARY RENDER (registry-driven) -->
        <component
            v-if="approvedVisual && approvedVisual.component"
            :is="approvedVisual.component"
            :value="formattedValue"
            :parsed-data="{ numericValue, displayValue: formattedValue }"
            :status="props.metric?.status ?? null"
            :size="compact ? 'sm' : 'lg'"
        />

        <!-- FALLBACK: BIG NUMBER (ALWAYS SHOW SOMETHING) -->
        <VisualBigNumberCard
            v-else
            :value="formattedValue"
            :parsed-data="{ displayValue: formattedValue }"
            :status="props.metric?.status ?? null"
            :size="compact ? 'sm' : 'lg'"
        />
    </div>
</template>
