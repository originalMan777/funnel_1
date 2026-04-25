<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { trackEvent } from '@/lib/analytics'
import { route } from 'ziggy-js';


type PopupRecord = {
  id: number
  name: string
  slug: string
  type: 'general' | 'buyer' | 'seller' | 'consultation' | 'resource'
  role: 'primary' | 'fallback' | 'standard'
  priority: number
  eyebrow?: string | null
  headline: string
  body?: string | null
  cta_text?: string | null
  success_message?: string | null
  layout: 'centered' | 'split' | 'banner'
  trigger_type: 'time' | 'scroll' | 'exit' | 'click'
  trigger_delay?: number | null
  trigger_scroll?: number | null
  target_pages?: string[]
  device: 'all' | 'desktop' | 'mobile'
  frequency: 'once_session' | 'once_day' | 'always'
  audience: 'everyone' | 'guests' | 'authenticated'
  suppress_if_lead_captured: boolean
  suppression_scope: 'this_popup_only' | 'all_lead_popups'
  form_fields: string[]
  lead_type: 'general' | 'buyer' | 'seller'
  post_submit_action: 'message' | 'redirect'
  post_submit_redirect_url?: string | null
}

type PopupManagerPayload = {
  pageKey: string | null
  leadCaptured: boolean
  isAuthenticated: boolean
  popups: PopupRecord[]
}

const page = usePage<any>()
const popupManager = computed<PopupManagerPayload>(() => page.props?.popupManager ?? {
  pageKey: null,
  leadCaptured: false,
  isAuthenticated: false,
  popups: [],
})

const pageKey = computed<string>(() => popupManager.value.pageKey ?? 'unknown')
const globalLeadCaptured = ref<boolean>(popupManager.value.leadCaptured)
const isAuthenticated = ref<boolean>(popupManager.value.isAuthenticated)
const activePopup = ref<PopupRecord | null>(null)
const isOpen = ref(false)
const submitted = ref(false)
const timeouts = ref<number[]>([])
const exitIntentBound = ref(false)
const eligibilityTracked = ref<Set<string>>(new Set())

const form = useForm({
  popup_id: null as number | null,
  page_key: pageKey.value,
  source_url: typeof window !== 'undefined' ? window.location.href : '',
  name: '',
  email: '',
  phone: '',
  message: '',
})

const visibleFields = computed(() => activePopup.value?.form_fields?.length ? activePopup.value.form_fields : ['name', 'email'])
const successMessage = computed(() => {
  if (page.props?.flash?.popupLeadSuccess) {
    return page.props.flash.popupLeadSuccess as string
  }

  return activePopup.value?.success_message || 'Thanks. We received your information.'
})

const sortedPopups = computed<PopupRecord[]>(() => {
  return [...(popupManager.value.popups ?? [])].sort((a, b) => a.priority - b.priority)
})

function readCookie(name: string) {
  if (typeof document === 'undefined') return null

  const match = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/[-.[\]{}()*+?^$|]/g, '\\$&')}=([^;]*)`))
  return match ? decodeURIComponent(match[1]) : null
}

function popupSeenKey(popup: PopupRecord) {
  return `nojo-popup-seen:${popup.slug}`
}

function popupSubmittedKey(popup: PopupRecord) {
  return `nojo-popup-submitted:${popup.slug}`
}

function isDesktop() {
  return typeof window !== 'undefined' ? window.matchMedia('(min-width: 768px)').matches : true
}

function passesDeviceRule(popup: PopupRecord) {
  if (popup.device === 'all') return true
  return popup.device === 'desktop' ? isDesktop() : !isDesktop()
}

function hasSeenPopup(popup: PopupRecord) {
  if (typeof window === 'undefined' || popup.frequency === 'always') return false

  const key = popupSeenKey(popup)

  if (popup.frequency === 'once_session') {
    return window.sessionStorage.getItem(key) === '1'
  }

  const value = window.localStorage.getItem(key)
  if (!value) return false

  return Number(value) > Date.now()
}

function markPopupSeen(popup: PopupRecord) {
  if (typeof window === 'undefined' || popup.frequency === 'always') return

  const key = popupSeenKey(popup)

  if (popup.frequency === 'once_session') {
    window.sessionStorage.setItem(key, '1')
    return
  }

  window.localStorage.setItem(key, String(Date.now() + 24 * 60 * 60 * 1000))
}

function hasSubmittedThisPopup(popup: PopupRecord) {
  if (typeof window === 'undefined') return false

  return window.localStorage.getItem(popupSubmittedKey(popup)) === '1'
    || readCookie(`nojo_popup_submitted_${popup.slug.replace(/-/g, '_')}`) === '1'
}

function isSuppressedByLeadState(popup: PopupRecord) {
  if (popup.suppression_scope === 'this_popup_only') {
    return hasSubmittedThisPopup(popup)
  }

  return popup.suppress_if_lead_captured && globalLeadCaptured.value
}

function eligiblePopupsByTrigger(trigger: PopupRecord['trigger_type']) {
  return sortedPopups.value.filter((popup) => {
    if (popup.trigger_type !== trigger) return false
    if (!passesDeviceRule(popup)) return false
    if (popup.audience === 'guests' && isAuthenticated.value) return false
    if (popup.audience === 'authenticated' && !isAuthenticated.value) return false
    if (isSuppressedByLeadState(popup)) return false
    if (hasSeenPopup(popup)) return false

    return true
  })
}

function trackEligiblePopups() {
  sortedPopups.value
    .filter((popup) => {
      if (!passesDeviceRule(popup)) return false
      if (popup.audience === 'guests' && isAuthenticated.value) return false
      if (popup.audience === 'authenticated' && !isAuthenticated.value) return false
      if (isSuppressedByLeadState(popup)) return false
      if (hasSeenPopup(popup)) return false

      return true
    })
    .forEach((popup) => {
      const key = `${pageKey.value}:${popup.id}`

      if (eligibilityTracked.value.has(key)) {
        return
      }

      eligibilityTracked.value.add(key)

      trackEvent({
        eventKey: 'popup.eligible',
        popupId: popup.id,
        surfaceKey: `popup.${popup.slug}`,
        properties: {
          source: 'popup_manager',
          trigger: popup.trigger_type,
        },
      })
    })
}

function getManualTriggerPopup(triggerValue: string | null) {
  const clickPopups = eligiblePopupsByTrigger('click')

  if (!clickPopups.length) return null
  if (!triggerValue || triggerValue === 'current-page' || triggerValue === 'default') return clickPopups[0]

  return clickPopups.find((popup) => {
    return [popup.slug, popup.type, popup.role, popup.lead_type, popup.name]
      .filter(Boolean)
      .map((item) => String(item).toLowerCase())
      .includes(triggerValue.toLowerCase())
  }) ?? clickPopups[0]
}

function openPopup(popup: PopupRecord, source: 'automatic' | 'manual' = 'automatic') {
  activePopup.value = popup
  submitted.value = false
  form.reset('name', 'email', 'phone', 'message')
  form.clearErrors()
  form.popup_id = popup.id
  form.page_key = pageKey.value
  form.source_url = typeof window !== 'undefined' ? window.location.href : ''
  isOpen.value = true
  markPopupSeen(popup)

  trackEvent({
    eventKey: 'popup.impression',
    popupId: popup.id,
    surfaceKey: `popup.${popup.slug}`,
    properties: {
      source,
      trigger: popup.trigger_type,
    },
  })

  trackEvent({
    eventKey: 'popup.opened',
    popupId: popup.id,
    surfaceKey: `popup.${popup.slug}`,
    properties: {
      source,
      trigger: popup.trigger_type,
    },
  })
}

function closePopup(reason: 'dismissed' | 'submitted' | 'completed' = 'dismissed') {
  if (reason === 'dismissed' && isOpen.value && activePopup.value && !submitted.value) {
    trackEvent({
      eventKey: 'popup.dismissed',
      popupId: activePopup.value.id,
      surfaceKey: `popup.${activePopup.value.slug}`,
      properties: {
        source: 'popup_manager',
        trigger: activePopup.value.trigger_type,
      },
    })
  }

  isOpen.value = false
}

function handleTimeTriggers() {
  eligiblePopupsByTrigger('time').forEach((popup) => {
    const timeoutId = window.setTimeout(() => {
      if (!isOpen.value && !isSuppressedByLeadState(popup)) {
        openPopup(popup, 'automatic')
      }
    }, Math.max((popup.trigger_delay ?? 0) * 1000, 0))

    timeouts.value.push(timeoutId)
  })
}

function handleScrollTrigger() {
  if (isOpen.value) return

  const scrollPopups = eligiblePopupsByTrigger('scroll')
  if (!scrollPopups.length) return

  const scrolled = window.scrollY + window.innerHeight
  const fullHeight = document.documentElement.scrollHeight
  const percentage = fullHeight > 0 ? Math.round((scrolled / fullHeight) * 100) : 0

  const match = scrollPopups.find((popup) => percentage >= (popup.trigger_scroll ?? 50))
  if (match) {
    openPopup(match, 'automatic')
  }
}

function handleExitIntent(event: MouseEvent) {
  if (isOpen.value || event.clientY > 10) return

  const popup = eligiblePopupsByTrigger('exit')[0]
  if (popup) {
    openPopup(popup, 'automatic')
  }
}

function handleManualTrigger(event: Event) {
  const target = event.target as HTMLElement | null
  const trigger = target?.closest?.('[data-popup-trigger]') as HTMLElement | null

  if (!trigger) return

  const popup = getManualTriggerPopup(trigger.getAttribute('data-popup-trigger'))
  if (!popup) return

  event.preventDefault()
  openPopup(popup, 'manual')
}

function setupTriggers() {
  if (typeof window === 'undefined') return

  handleTimeTriggers()
  window.addEventListener('scroll', handleScrollTrigger, { passive: true })
  document.addEventListener('click', handleManualTrigger)

  if (!exitIntentBound.value) {
    document.addEventListener('mouseout', handleExitIntent)
    exitIntentBound.value = true
  }
}

function clearTriggers() {
  if (typeof window === 'undefined') return

  timeouts.value.forEach((id) => window.clearTimeout(id))
  timeouts.value = []
  window.removeEventListener('scroll', handleScrollTrigger)
  document.removeEventListener('click', handleManualTrigger)

  if (exitIntentBound.value) {
    document.removeEventListener('mouseout', handleExitIntent)
    exitIntentBound.value = false
  }
}

function markSubmissionState(popup: PopupRecord) {
  if (typeof window === 'undefined') return

  window.localStorage.setItem(popupSubmittedKey(popup), '1')

  if (popup.suppression_scope === 'all_lead_popups') {
    window.localStorage.setItem('nojo-lead-captured', '1')
    globalLeadCaptured.value = true
  }
}

function submitLead() {
  if (!activePopup.value) return

  form.popup_id = activePopup.value.id
  form.page_key = pageKey.value
  form.source_url = typeof window !== 'undefined' ? window.location.href : ''

 form.post(route('popup-leads.store'), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      if (!activePopup.value) return

      markSubmissionState(activePopup.value)

      if (activePopup.value.post_submit_action === 'redirect' && activePopup.value.post_submit_redirect_url) {
        closePopup('submitted')
        window.location.href = activePopup.value.post_submit_redirect_url
        return
      }

      submitted.value = true
    },
  })
}

watch(isOpen, (value) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = value ? 'hidden' : ''
})

watch(popupManager, (value) => {
  globalLeadCaptured.value = value.leadCaptured || (typeof window !== 'undefined' && window.localStorage.getItem('nojo-lead-captured') === '1')
  isAuthenticated.value = value.isAuthenticated
}, { immediate: true, deep: true })

watch(sortedPopups, () => {
  clearTriggers()
  trackEligiblePopups()

  if (sortedPopups.value.length) {
    setupTriggers()
  }
}, { immediate: true })

watch(pageKey, () => {
  eligibilityTracked.value = new Set()
})

onMounted(() => {
  globalLeadCaptured.value = popupManager.value.leadCaptured || window.localStorage.getItem('nojo-lead-captured') === '1'

  if (page.props?.flash?.popupLeadSuccess && activePopup.value) {
    submitted.value = true
  }
})

onBeforeUnmount(() => {
  clearTriggers()

  if (typeof document !== 'undefined') {
    document.body.style.overflow = ''
  }
})
</script>

<template>
  <Teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isOpen && activePopup" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/55" @click="closePopup('dismissed')" />

        <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5">
          <div class="flex items-start justify-between border-b border-gray-100 px-6 py-5 md:px-8">
            <div>
              <p v-if="activePopup.eyebrow" class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">
                {{ activePopup.eyebrow }}
              </p>
              <h2 class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl">
                {{ activePopup.headline }}
              </h2>
            </div>

            <button
              type="button"
              class="ml-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:bg-gray-50"
              @click="closePopup('dismissed')"
            >
              ×
            </button>
          </div>

          <div class="px-6 py-6 md:px-8 md:py-8">
            <div v-if="submitted" class="space-y-4">
              <p class="text-base leading-7 text-gray-600">
                {{ successMessage }}
              </p>

              <div class="flex gap-3 pt-2">
                <button
                  type="button"
                  class="inline-flex rounded-lg bg-gray-900 px-5 py-3 text-sm font-medium text-white hover:bg-gray-800"
                  @click="closePopup('completed')"
                >
                  Close
                </button>
              </div>
            </div>

            <div v-else class="grid gap-8 md:grid-cols-[1fr_0.95fr]">
              <div class="space-y-4">
                <p v-if="activePopup.body" class="text-base leading-7 text-gray-600">
                  {{ activePopup.body }}
                </p>

                <div class="rounded-2xl bg-gray-50 p-4 text-sm leading-6 text-gray-600">
                  <p><span class="font-medium text-gray-900">Audience:</span> {{ activePopup.audience }}</p>
                  <p><span class="font-medium text-gray-900">Role:</span> {{ activePopup.role }}</p>
                  <p><span class="font-medium text-gray-900">Priority:</span> {{ activePopup.priority }}</p>
                </div>
              </div>

              <div>
                <form class="space-y-4" @submit.prevent="submitLead">
                  <input v-model="form.popup_id" type="hidden" />
                  <input v-model="form.page_key" type="hidden" />
                  <input v-model="form.source_url" type="hidden" />

                  <div v-if="visibleFields.includes('name')" class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Name</label>
                    <input
                      v-model="form.name"
                      type="text"
                      class="w-full rounded-xl border border-gray-200 px-4 py-3 outline-none focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                      placeholder="Your name"
                    />
                    <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                  </div>

                  <div v-if="visibleFields.includes('email')" class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Email</label>
                    <input
                      v-model="form.email"
                      type="email"
                      class="w-full rounded-xl border border-gray-200 px-4 py-3 outline-none focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                      placeholder="you@example.com"
                    />
                    <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
                  </div>

                  <div v-if="visibleFields.includes('phone')" class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Phone</label>
                    <input
                      v-model="form.phone"
                      type="text"
                      class="w-full rounded-xl border border-gray-200 px-4 py-3 outline-none focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                      placeholder="Phone number"
                    />
                    <p v-if="form.errors.phone" class="text-sm text-red-600">{{ form.errors.phone }}</p>
                  </div>

                  <div v-if="visibleFields.includes('message')" class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Message</label>
                    <textarea
                      v-model="form.message"
                      rows="4"
                      class="w-full rounded-xl border border-gray-200 px-4 py-3 outline-none focus:border-gray-300 focus:ring-2 focus:ring-gray-200"
                      placeholder="Tell us a little about what you need"
                    />
                    <p v-if="form.errors.message" class="text-sm text-red-600">{{ form.errors.message }}</p>
                  </div>

                  <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button
                      type="submit"
                      class="inline-flex rounded-lg bg-gray-900 px-5 py-3 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-60"
                      :disabled="form.processing"
                    >
                      {{ activePopup.cta_text || 'Submit' }}
                    </button>

                    <button
                      type="button"
                      class="inline-flex rounded-lg border border-gray-200 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
                      @click="closePopup('dismissed')"
                    >
                      Not now
                    </button>
                  </div>
                </form>

                <div v-if="activePopup.trigger_type === 'click'" class="mt-6 text-xs leading-6 text-gray-500">
                  This popup opens from a manual click trigger. Add
                  <code class="rounded bg-gray-100 px-1 py-0.5 text-[11px]">data-popup-trigger="current-page"</code>
                  to any link or button on this page.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>
