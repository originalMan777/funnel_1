<script setup lang="ts">
import { computed } from 'vue'
import AnalyticsDataTable from '@/components/admin/analytics/AnalyticsDataTable.vue'
import { formatNumber } from '@/components/admin/analytics/formatters'

type TrendColumn = {
  key: string
  label: string
}

const props = defineProps<{
  rows: Array<Record<string, string | number>>
  columns: TrendColumn[]
}>()

const maxima = computed(() => {
  const values: Record<string, number> = {}

  for (const column of props.columns) {
    values[column.key] = Math.max(0, ...props.rows.map((row) => Number(row[column.key] ?? 0)))
  }

  return values
})

const widthFor = (key: string, value: number) => {
  const max = maxima.value[key] || 0

  if (max <= 0) {
    return '0%'
  }

  return `${Math.max((value / max) * 100, 6)}%`
}
</script>

<template>
  <AnalyticsDataTable :col-count="columns.length + 1" :show-empty="rows.length === 0">
    <template #head>
      <tr>
        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
          Day
        </th>
        <th
          v-for="column in columns"
          :key="column.key"
          class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600"
        >
          {{ column.label }}
        </th>
      </tr>
    </template>

    <template #empty>
      <td :colspan="columns.length + 1" class="px-4 py-6 text-sm text-slate-600">
        No rollup trend data found for this range.
      </td>
    </template>

    <tr v-for="row in rows" :key="String(row.date)">
      <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ row.date }}</td>
      <td
        v-for="column in columns"
        :key="column.key"
        class="px-4 py-3 text-sm text-slate-700"
      >
        <div class="space-y-1">
          <p>{{ formatNumber(Number(row[column.key] ?? 0)) }}</p>
          <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
            <div
              class="h-full rounded-full bg-slate-900"
              :style="{ width: widthFor(column.key, Number(row[column.key] ?? 0)) }"
            />
          </div>
        </div>
      </td>
    </tr>
  </AnalyticsDataTable>
</template>
