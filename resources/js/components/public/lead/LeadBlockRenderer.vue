<script setup lang="ts">
import type { LeadBlockRenderModel } from '@/types/leadBlocks';
import ResourceLeadBlock from '@/components/public/lead/ResourceLeadBlock.vue';
import ServiceLeadBlock from '@/components/public/lead/ServiceLeadBlock.vue';
import OfferLeadBlock from '@/components/public/lead/OfferLeadBlock.vue';

const props = defineProps<{
    model: LeadBlockRenderModel;
    previewMode?: boolean;
}>();
</script>

<template>
    <ResourceLeadBlock
        v-if="props.model.type === 'resource'"
        :model="props.model"
        :preview-mode="props.previewMode"
    />

    <ServiceLeadBlock
        v-else-if="props.model.type === 'service'"
        :model="props.model"
        :preview-mode="props.previewMode"
    />

    <OfferLeadBlock
        v-else-if="props.model.type === 'offer'"
        :model="props.model"
        :preview-mode="props.previewMode"
    />
</template>
