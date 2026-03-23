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
    <div v-if="model" class="relative left-1/2 w-screen -translate-x-1/2">
        <!-- Align lead blocks to the same outer width/padding as the hero -->
        <div class="mx-auto max-w-7xl px-6 md:px-10">
            <LeadBlockRenderer :model="model" />
        </div>
    </div>
</template>
