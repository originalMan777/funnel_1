<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import LeadBlockRenderer from '@/components/public/lead/LeadBlockRenderer.vue';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

const props = defineProps<{
    slotKey: string;
}>();

const page = usePage<any>();

const model = computed<LeadBlockRenderModel | null>(() => {
    const leadSlots = (page.props?.leadSlots ?? {}) as Record<string, LeadBlockRenderModel | null>;
    return leadSlots[props.slotKey] ?? null;
});
</script>

<template>
    <div v-if="model" class="w-full">
        <LeadBlockRenderer :model="model" />
    </div>
</template>
