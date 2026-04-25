<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber } from '@/components/admin/analytics/formatters'

type TrendRow = Record<string, string | number>
type TrendSeries = {
  key: string
  label: string
  colorClass?: string
}

const props = defineProps<{
  rows: TrendRow[]
  series: TrendSeries[]
}>()

const maxValue = computed(() => {
  const values = props.rows.flatMap((row) => props.series.map((item) => Number(row[item.key] ?? 0)))

  return Math.max(0, ...values)
})

const heightFor = (value: number) => {
  if (maxValue.value <= 0) {
    return '8%'
  }

  return `${Math.max((value / maxValue.value) * 100, 8)}%`
}
</script>

<template>
  <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
    <div class="flex flex-wrap items-center gap-3">
      <div
        v-for="item in series"
        :key="item.key"
        class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"
      >
        <span class="h-2.5 w-2.5 rounded-full" :class="item.colorClass || 'bg-slate-900'" />
        {{ item.label }}
      </div>
    </div>

    <div class="mt-6 grid min-h-[14rem] grid-cols-[repeat(auto-fit,minmax(2.5rem,1fr))] items-end gap-3">
      <div
        v-for="row in rows"
        :key="String(row.date)"
        class="flex min-w-0 flex-col items-center gap-2"
      >
        <div class="flex h-44 w-full items-end justify-center gap-1 rounded-2xl bg-slate-50 px-1 py-2">
          <div
            v-for="item in series"
            :key="`${row.date}-${item.key}`"
            class="w-full rounded-full"
            :class="item.colorClass || 'bg-slate-900'"
            :style="{ height: heightFor(Number(row[item.key] ?? 0)) }"
            :title="`${item.label}: ${formatNumber(Number(row[item.key] ?? 0))}`"
          />
        </div>
        <div class="text-center">
          <p class="text-xs font-medium text-slate-700">{{ row.date }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
