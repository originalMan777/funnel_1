<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, reactive, watch } from 'vue'

type FilterPreset = {
  label: string
  days: number
}

type FilterTab = {
  name: string
  href: string
}

const props = defineProps<{
  filters: {
    from: string
    to: string
    presets: FilterPreset[]
  }
  currentRoute: string
  tabs?: FilterTab[]
  showDateControls?: boolean
}>()

const page = usePage()
const state = reactive({
  from: props.filters.from,
  to: props.filters.to,
})

watch(
  () => props.filters,
  (filters) => {
    state.from = filters.from
    state.to = filters.to
  },
  { deep: true },
)

const resolvedTabs = computed<FilterTab[]>(() => props.tabs ?? [])
const currentUrl = computed(() => String(page.url || ''))

const normalizePath = (url: string) => {
  try {
    return new URL(url, window.location.origin).pathname.replace(/\/+$/, '')
  } catch {
    return url.replace(/\/+$/, '')
  }
}

const isActive = (href: string) => {
  const current = normalizePath(currentUrl.value)
  const target = normalizePath(href)

  return current === target || current.startsWith(`${target}/`)
}

const applyFilters = () => {
  router.get(
    props.currentRoute,
    {
      from: state.from,
      to: state.to,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  )
}

const clearFilters = () => {
  router.get(
    props.currentRoute,
    {},
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  )
}

const formatDateInput = (date: Date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

const presetHref = (days: number) => {
  const end = new Date()
  const start = new Date()
  start.setDate(end.getDate() - (days - 1))

  const search = new URLSearchParams({
    from: formatDateInput(start),
    to: formatDateInput(end),
  })

  return `${props.currentRoute}?${search.toString()}`
}
</script>

<template>
  <div class="space-y-3 rounded-[1.75rem] border border-slate-200/80 bg-white/90 p-3 shadow-sm shadow-slate-200/60 backdrop-blur">
    <div
      v-if="showDateControls !== false || $slots.actions"
      class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between"
    >
      <form
        v-if="showDateControls !== false"
        class="grid gap-3 sm:grid-cols-2 xl:w-[27rem]"
        @submit.prevent="applyFilters"
      >
        <label class="text-sm text-slate-700">
          <span class="mb-1 block font-medium">From</span>
          <input
            v-model="state.from"
            type="date"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-slate-500"
          >
        </label>
        <label class="text-sm text-slate-700">
          <span class="mb-1 block font-medium">To</span>
          <input
            v-model="state.to"
            type="date"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-slate-500"
          >
        </label>
        <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
          <button
            type="submit"
            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
          >
            Apply
          </button>
          <button
            type="button"
            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            @click="clearFilters"
          >
            Reset
          </button>
          <Link
            v-for="preset in filters.presets"
            :key="preset.days"
            :href="presetHref(preset.days)"
            class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 transition hover:border-slate-300 hover:bg-white"
            preserve-state
            preserve-scroll
          >
            {{ preset.label }}
          </Link>
        </div>
      </form>

      <slot name="actions" />
    </div>

    <nav v-if="resolvedTabs.length" class="flex flex-wrap gap-2" aria-label="Analytics reports">
      <Link
        v-for="tab in resolvedTabs"
        :key="tab.href"
        :href="`${tab.href}?from=${filters.from}&to=${filters.to}`"
        class="rounded-xl px-4 py-2 text-sm font-medium transition"
        :class="isActive(tab.href) ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
        preserve-state
        preserve-scroll
      >
        {{ tab.name }}
      </Link>
    </nav>
  </div>
</template>
