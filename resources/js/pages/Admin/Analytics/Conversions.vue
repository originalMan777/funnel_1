<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/AppLayouts/AdminLayout.vue'
import AnalyticsDataTable from '@/components/admin/analytics/AnalyticsDataTable.vue'
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue'
import AnalyticsKpiCard from '@/components/admin/analytics/AnalyticsKpiCard.vue'
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue'
import AnalyticsTrendTable from '@/components/admin/analytics/AnalyticsTrendTable.vue'
import { formatDuration, formatNumber } from '@/components/admin/analytics/formatters'

defineProps<{
  filters: {
    from: string
    to: string
    presets: Array<{ label: string; days: number }>
  }
  report: {
    total: number
    trend: Array<{
      date: string
      conversions: number
    }>
    conversion_types: Array<{
      conversion_type_id: number
      label: string
      total: number
    }>
    average_time_to_conversion_seconds: number | null
    median_time_to_conversion_seconds: number | null
  }
}>()

const trendColumns = [{ key: 'conversions', label: 'Conversions' }]
</script>

<template>
  <Head title="Analytics Conversions" />

  <AdminLayout>
    <AnalyticsShell>
      <template #header>
        <AnalyticsHeader
          title="Conversions"
          description="Track conversion totals, daily conversion movement, and the current mix of conversion types from analytics-owned facts."
          :filters="filters"
          :current-route="route('admin.analytics.conversions.index')"
        />
      </template>
      <div class="grid gap-4 md:grid-cols-4">
        <AnalyticsKpiCard label="Total Conversions" :value="formatNumber(report.total)" tone="emerald" />
        <AnalyticsKpiCard label="Conversion Types" :value="formatNumber(report.conversion_types.length)" />
        <AnalyticsKpiCard
          label="Average Elapsed Time to Conversion"
          :value="formatDuration(report.average_time_to_conversion_seconds)"
          hint="Event-based elapsed time from session start to the first analytics conversion."
          tone="sky"
        />
        <AnalyticsKpiCard
          label="Median Elapsed Time to Conversion"
          :value="formatDuration(report.median_time_to_conversion_seconds)"
          hint="Event-based median time from session start to first analytics conversion."
        />
      </div>

      <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/50">
        <h2 class="text-lg font-semibold text-gray-900">Daily Conversion Trend</h2>
        <p class="mt-1 text-sm text-gray-600">Each row is summed from daily rollups for the selected date range.</p>
        <div class="mt-4">
          <AnalyticsTrendTable :rows="report.trend" :columns="trendColumns" />
        </div>
      </section>

      <AnalyticsDataTable :col-count="2" :show-empty="report.conversion_types.length === 0">
        <template #description>
          Conversion types reflect the current standalone analytics conversion catalog used by the projection layer.
        </template>
        <template #head>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversion Type</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Total</th>
          </tr>
        </template>
        <template #empty>
          <td colspan="2" class="px-4 py-6 text-sm text-slate-600">No conversion rollups found for this range.</td>
        </template>
        <tr v-for="row in report.conversion_types" :key="row.conversion_type_id">
          <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ row.label }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.total) }}</td>
        </tr>
      </AnalyticsDataTable>
    </AnalyticsShell>
  </AdminLayout>
</template>
