<script setup lang="ts">
import { formatNumber } from '@/components/admin/analytics/formatters'

defineProps<{
  steps: Array<{
    key: string
    label: string
    count: number
    dropOff?: number | null
  }>
}>()
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="step in steps"
      :key="step.key"
      class="rounded-[1.35rem] border border-slate-200 bg-slate-50 p-4"
    >
      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="font-medium text-slate-900">{{ step.label }}</p>
          <p class="text-sm text-slate-500">Sessions reaching this step</p>
        </div>
        <div class="text-right">
          <p class="text-lg font-semibold text-slate-900">{{ formatNumber(step.count) }}</p>
          <p v-if="step.dropOff !== undefined && step.dropOff !== null" class="text-sm text-slate-500">
            Drop-off: {{ formatNumber(step.dropOff) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
