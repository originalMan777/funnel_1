<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  items: Array<{ label: string; value: number }>
}>()

const maxValue = computed(() => Math.max(0, ...props.items.map((item) => item.value)))

const widthFor = (value: number) => {
  if (maxValue.value <= 0) {
    return '10%'
  }

  return `${Math.max((value / maxValue.value) * 100, 10)}%`
}
</script>

<template>
  <div class="space-y-2">
    <div v-for="item in items" :key="item.label" class="space-y-1">
      <div class="flex items-center justify-between gap-3 text-xs text-slate-500">
        <span class="truncate">{{ item.label }}</span>
        <span class="font-medium text-slate-700">{{ item.value }}</span>
      </div>
      <div class="h-2 overflow-hidden rounded-full bg-slate-200">
        <div class="h-full rounded-full bg-slate-900" :style="{ width: widthFor(item.value) }" />
      </div>
    </div>
  </div>
</template>
