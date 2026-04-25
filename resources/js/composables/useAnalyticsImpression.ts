import { onBeforeUnmount, onMounted, type Ref } from 'vue'
import { trackEvent, type AnalyticsEventInput } from '@/lib/analytics'

type ImpressionOptions = {
    threshold?: number
}

export function useAnalyticsImpression(
    elementRef: Ref<Element | { $el?: Element | null } | null>,
    buildEvent: () => AnalyticsEventInput | null,
    options: ImpressionOptions = {},
) {
    let observer: IntersectionObserver | null = null
    let tracked = false

    const stop = () => {
        observer?.disconnect()
        observer = null
    }

    const start = () => {
        const target =
            elementRef.value instanceof Element
                ? elementRef.value
                : elementRef.value?.$el ?? null

        if (typeof window === 'undefined' || tracked || !target) {
            return
        }

        observer = new IntersectionObserver(
            (entries) => {
                const entry = entries[0]

                if (!entry?.isIntersecting || tracked) {
                    return
                }

                const payload = buildEvent()

                if (payload) {
                    trackEvent(payload)
                }

                tracked = true
                stop()
            },
            {
                threshold: options.threshold ?? 0.45,
            },
        )

        observer.observe(target)
    }

    onMounted(start)
    onBeforeUnmount(stop)
}
