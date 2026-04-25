<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber } from '@/components/admin/analytics/formatters'

const props = defineProps<{
  rows: Array<{
    label: string
    value: number
    context?: string | null
  }>
}>()

const maxValue = computed(() => Math.max(0, ...props.rows.map((row) => row.value)))

const widthFor = (value: number) => {
  if (maxValue.value <= 0) {
    return '8%'
  }

  return `${Math.max((value / maxValue.value) * 100, 8)}%`
}
</script>

<template>
  <div class="space-y-3">
    <div v-for="row in rows" :key="row.label" class="rounded-2xl bg-slate-50 p-4">
      <div class="flex items-center justify-between gap-3">
        <div>
          <p class="font-medium text-slate-900">{{ row.label }}</p>
          <p v-if="row.context" class="text-sm text-slate-500">{{ row.context }}</p>
        </div>
        <p class="text-sm font-medium text-slate-700">{{ formatNumber(row.value) }}</p>
      </div>
      <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
        <div class="h-full rounded-full bg-slate-900" :style="{ width: widthFor(row.value) }" />
      </div>
    </div>
  </div>
</template>
