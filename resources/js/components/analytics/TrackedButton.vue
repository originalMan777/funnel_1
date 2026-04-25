<script setup lang="ts">
import { ref } from 'vue'
import { trackEvent, type AnalyticsEventInput } from '@/lib/analytics'
import { useAnalyticsImpression } from '@/composables/useAnalyticsImpression'

const props = withDefaults(
    defineProps<{
        ctaKey: string
        surfaceKey?: string | null
        leadBoxId?: number | null
        leadSlotId?: number | null
        popupId?: number | null
        impressionPlacement?: string | null
        trackImpression?: boolean
    }>(),
    {
        surfaceKey: null,
        leadBoxId: null,
        leadSlotId: null,
        popupId: null,
        impressionPlacement: null,
        trackImpression: true,
    },
)

const emit = defineEmits<{
    click: [event: MouseEvent]
}>()

const elementRef = ref<Element | null>(null)

const buildBaseEvent = (eventKey: string): AnalyticsEventInput => ({
    eventKey,
    ctaKey: props.ctaKey,
    surfaceKey: props.surfaceKey,
    leadBoxId: props.leadBoxId,
    leadSlotId: props.leadSlotId,
    popupId: props.popupId,
})

useAnalyticsImpression(
    elementRef,
    () => {
        if (!props.trackImpression) {
            return null
        }

        return {
            ...buildBaseEvent('cta.impression'),
            properties: {
                placement: props.impressionPlacement ?? undefined,
                source: 'tracked_button',
            },
        }
    },
    {
        threshold: 0.35,
    },
)

const handleClick = (event: MouseEvent) => {
    trackEvent({
        ...buildBaseEvent('cta.click'),
        properties: {
            source: 'tracked_button',
        },
    })

    emit('click', event)
}
</script>

<template>
    <button ref="elementRef" v-bind="$attrs" @click="handleClick">
        <slot />
    </button>
</template>
