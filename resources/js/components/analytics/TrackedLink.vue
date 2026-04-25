<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'
import { trackEvent, type AnalyticsEventInput } from '@/lib/analytics'
import { useAnalyticsImpression } from '@/composables/useAnalyticsImpression'

const props = withDefaults(
    defineProps<{
        href: string
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
                source: 'tracked_link',
            },
        }
    },
    {
        threshold: 0.35,
    },
)

const handleClick = () => {
    trackEvent({
        ...buildBaseEvent('cta.click'),
        properties: {
            source: 'tracked_link',
        },
    })
}

onMounted(() => {
    if (!elementRef.value) {
        return
    }
})
</script>

<template>
    <Link ref="elementRef" :href="href" v-bind="$attrs" @click="handleClick">
        <slot />
    </Link>
</template>
