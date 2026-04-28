<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'

type SidebarItem = { label: string; value?: string }

const page = usePage<any>()
const cfg = computed(() => page.props?.ui?.rightSidebar ?? { enabled: true })
const enabled = computed(() => cfg.value.enabled !== false)
const title = computed(() => cfg.value.title ?? 'Context')
const items = computed<SidebarItem[]>(() => cfg.value.items ?? [])
const collapsible = computed(() => cfg.value.collapsible !== false)

const isOpen = ref(true)
const widthPx = computed(() => (typeof cfg.value.width === 'number' ? cfg.value.width : 280))
const collapsedWidthPx = computed(() =>
  typeof cfg.value.collapsedWidth === 'number' ? cfg.value.collapsedWidth : 44
)
const currentWidth = computed(() => (isOpen.value ? widthPx.value : collapsedWidthPx.value))
const STORAGE_KEY = 'ui.rightSidebar.isOpen'

onMounted(() => {
  if (!enabled.value) return

  if (typeof cfg.value.collapsed === 'boolean') {
    isOpen.value = !cfg.value.collapsed
    return
  }

  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved === '0') isOpen.value = false
    if (saved === '1') isOpen.value = true
  } catch {
    // ignore
  }
})

watch(
  () => cfg.value.collapsed,
  (collapsed) => {
    if (typeof collapsed === 'boolean') isOpen.value = !collapsed
  }
)

watch(isOpen, (val) => {
  if (!enabled.value) return

  try {
    localStorage.setItem(STORAGE_KEY, val ? '1' : '0')
  } catch {
    // ignore
  }
})

function toggle() {
  if (!collapsible.value) return
  isOpen.value = !isOpen.value
}
</script>

<template>
  <aside
    v-if="enabled"
    class="h-full shrink-0 overflow-hidden border-l border-gray-200 bg-gray-50 transition-[width] duration-200"
    :style="{
      width: `${currentWidth}px`,
      minWidth: `${currentWidth}px`,
      maxWidth: `${currentWidth}px`,
      flex: `0 0 ${currentWidth}px`,
    }"
  >
    <div class="flex items-center justify-between border-b" :class="isOpen ? 'p-3' : 'p-2'">
      <div v-if="isOpen" class="text-sm font-semibold text-gray-700">
        {{ title }}
      </div>

      <button
        v-if="collapsible"
        type="button"
        class="rounded border border-gray-300 bg-white px-2 py-1 text-xs text-gray-700 hover:bg-gray-100"
        @click="toggle"
      >
        {{ isOpen ? 'Collapse' : 'Expand' }}
      </button>
    </div>

    <div v-show="isOpen" class="flex-1 space-y-2 overflow-y-auto p-4">
      <div v-if="items.length === 0" class="text-sm text-gray-500">No context.</div>

      <div v-for="(item, idx) in items" :key="idx" class="text-sm">
        <div class="text-xs uppercase tracking-wide text-gray-500">{{ item.label }}</div>
        <div class="text-gray-800">{{ item.value ?? '—' }}</div>
      </div>
    </div>

    <div v-if="!isOpen" class="flex h-full items-center justify-center">
      <span class="select-none rotate-90 text-[10px] font-semibold text-gray-500">Context</span>
    </div>
  </aside>
</template>
