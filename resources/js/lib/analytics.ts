export type AnalyticsBootstrap = {
    enabled: boolean
    ready?: boolean
    ingest_url?: string
    page?: {
        key: string | null
    }
    visitor?: {
        key: string
    }
    session?: {
        key: string
        inactivity_timeout_minutes: number
    }
} | null

export type AnalyticsEventInput = {
    eventKey: string
    occurredAt?: string
    pageKey?: string | null
    ctaKey?: string | null
    surfaceKey?: string | null
    leadBoxId?: number | null
    leadSlotId?: number | null
    popupId?: number | null
    subjectType?: string | null
    subjectId?: number | null
    properties?: Record<string, unknown> | null
}

type InertiaPagePayload = {
    component?: string
    url?: string
    props?: {
        analytics?: AnalyticsBootstrap
    }
}

type PendingEventPayload = {
    event_key: string
    occurred_at?: string
    page_key?: string | null
    cta_key?: string | null
    surface_key?: string | null
    lead_box_id?: number | null
    lead_slot_id?: number | null
    popup_id?: number | null
    subject_type?: string | null
    subject_id?: number | null
    properties?: Record<string, unknown> | null
}

let analyticsContext: AnalyticsBootstrap = null
let flushTimer: number | null = null
let lastPageViewFingerprint: string | null = null
const pendingEvents: PendingEventPayload[] = []

const csrfToken = () =>
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content')
        ?.trim() ?? ''

const canTrack = () =>
    analyticsContext?.enabled === true &&
    analyticsContext?.ready === true &&
    typeof analyticsContext.ingest_url === 'string' &&
    analyticsContext.ingest_url.length > 0

const normalizeProperties = (properties?: Record<string, unknown> | null) => {
    if (!properties) {
        return null
    }

    const entries = Object.entries(properties).filter(([, value]) => {
        return value !== null && value !== undefined && value !== ''
    })

    return entries.length ? Object.fromEntries(entries) : null
}

const scheduleFlush = () => {
    if (flushTimer !== null || !canTrack()) {
        return
    }

    flushTimer = window.setTimeout(() => {
        void flushEvents()
    }, 120)
}

const flushEvents = async (useBeacon = false) => {
    if (flushTimer !== null) {
        window.clearTimeout(flushTimer)
        flushTimer = null
    }

    if (!canTrack() || pendingEvents.length === 0) {
        return
    }

    const payload = pendingEvents.splice(0, pendingEvents.length)

    const requestBody = JSON.stringify({
        visitor_key: analyticsContext?.visitor?.key ?? null,
        session_key: analyticsContext?.session?.key ?? null,
        events: payload,
    })

    if (
        useBeacon &&
        typeof navigator !== 'undefined' &&
        typeof navigator.sendBeacon === 'function'
    ) {
        const sent = navigator.sendBeacon(
            analyticsContext!.ingest_url!,
            new Blob([requestBody], { type: 'application/json' }),
        )

        if (!sent) {
            pendingEvents.unshift(...payload)
        }

        return
    }

    try {
        await window.fetch(analyticsContext!.ingest_url!, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: requestBody,
        })
    } catch {
        pendingEvents.unshift(...payload)
    }
}

export const initializeAnalyticsContext = (payload: AnalyticsBootstrap) => {
    analyticsContext = payload

    if (typeof window !== 'undefined') {
        ;(window as Window & { __analytics?: AnalyticsBootstrap }).__analytics = payload
    }
}

export const getAnalyticsContext = () => analyticsContext

export const trackEvent = (input: AnalyticsEventInput) => {
    if (!canTrack()) {
        return
    }

    pendingEvents.push({
        event_key: input.eventKey,
        occurred_at: input.occurredAt,
        page_key: input.pageKey ?? analyticsContext?.page?.key ?? null,
        cta_key: input.ctaKey ?? null,
        surface_key: input.surfaceKey ?? null,
        lead_box_id: input.leadBoxId ?? null,
        lead_slot_id: input.leadSlotId ?? null,
        popup_id: input.popupId ?? null,
        subject_type: input.subjectType ?? null,
        subject_id: input.subjectId ?? null,
        properties: normalizeProperties(input.properties),
    })

    scheduleFlush()
}

export const trackPageView = (page: InertiaPagePayload) => {
    const context = page.props?.analytics ?? analyticsContext
    const pageKey = context?.page?.key ?? null

    if (!context?.enabled || !context?.ready || !pageKey) {
        return
    }

    const fingerprint = `${page.component ?? 'unknown'}|${page.url ?? 'unknown'}|${pageKey}`

    if (fingerprint === lastPageViewFingerprint) {
        return
    }

    lastPageViewFingerprint = fingerprint

    trackEvent({
        eventKey: 'page.view',
        pageKey,
        properties: {
            source: 'inertia',
        },
    })
}

export const flushAnalyticsEvents = (useBeacon = false) => {
    if (!canTrack() || pendingEvents.length === 0) {
        return
    }

    void flushEvents(useBeacon)
}
