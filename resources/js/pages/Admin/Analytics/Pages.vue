<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/AppLayouts/AdminLayout.vue'
import AnalyticsDataTable from '@/components/admin/analytics/AnalyticsDataTable.vue'
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue'
import AnalyticsKpiCard from '@/components/admin/analytics/AnalyticsKpiCard.vue'
import AnalyticsMetricCell from '@/components/admin/analytics/AnalyticsMetricCell.vue'
import AnalyticsRateBadge from '@/components/admin/analytics/AnalyticsRateBadge.vue'
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue'
import { formatDuration, formatNumber, formatPercent } from '@/components/admin/analytics/formatters'

const props = defineProps<{
  filters: {
    from: string
    to: string
    presets: Array<{ label: string; days: number }>
  }
  report: {
    rows: Array<{
      id: number
      key: string
      label: string
      category: string | null
      views: number
      conversions: number
      conversion_rate: number | null
      avg_time_to_cta_click_seconds: number | null
      avg_time_to_conversion_seconds: number | null
    }>
  }
}>()

const totals = computed(() => ({
  views: props.report.rows.reduce((sum, row) => sum + row.views, 0),
  conversions: props.report.rows.reduce((sum, row) => sum + row.conversions, 0),
}))
</script>

<template>
  <Head title="Analytics Pages" />

  <AdminLayout>
    <AnalyticsShell>
      <template #header>
        <AnalyticsHeader
          title="Pages"
          description="Review which analytics pages are earning views, producing conversions, and how long event-based journeys take to reach CTA clicks or conversions."
          :filters="filters"
          :current-route="route('admin.analytics.pages.index')"
        />
      </template>
      <div class="grid gap-4 md:grid-cols-3">
        <AnalyticsKpiCard label="Tracked Pages" :value="formatNumber(report.rows.length)" />
        <AnalyticsKpiCard label="Total Views" :value="formatNumber(totals.views)" tone="sky" />
        <AnalyticsKpiCard label="Total Conversions" :value="formatNumber(totals.conversions)" tone="emerald" />
      </div>

      <AnalyticsDataTable :col-count="7" :show-empty="report.rows.length === 0">
        <template #description>
          Conversion rate is shown only where the page has rollup-backed views in the selected range. Time metrics are event-based elapsed times, not active-attention measurements.
        </template>
        <template #head>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Page</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Category</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Views</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversion Rate</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Elapsed View to CTA Click</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Elapsed View to Conversion</th>
          </tr>
        </template>
        <template #empty>
          <td colspan="7" class="px-4 py-6 text-sm text-slate-600">No page rollups found for this range.</td>
        </template>
        <tr v-for="row in report.rows" :key="row.id">
          <td class="px-4 py-3 text-sm">
            <AnalyticsMetricCell :value="row.label" :meta="row.key" />
          </td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ row.category || '—' }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.views) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.conversions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">
            <AnalyticsRateBadge :value="formatPercent(row.conversion_rate)" />
          </td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.avg_time_to_cta_click_seconds) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.avg_time_to_conversion_seconds) }}</td>
        </tr>
      </AnalyticsDataTable>
    </AnalyticsShell>
  </AdminLayout>
</template>
