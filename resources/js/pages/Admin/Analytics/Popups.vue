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
      type: string | null
      eligible: number
      impressions: number
      opens: number
      dismissals: number
      submissions: number
      open_rate: number | null
      submission_rate: number | null
      avg_open_to_submit_seconds: number | null
      avg_open_to_dismiss_seconds: number | null
      median_open_to_submit_seconds: number | null
      conversion_touch_conversions: number
    }>
  }
}>()

const totals = computed(() => ({
  eligible: props.report.rows.reduce((sum, row) => sum + row.eligible, 0),
  impressions: props.report.rows.reduce((sum, row) => sum + row.impressions, 0),
  opens: props.report.rows.reduce((sum, row) => sum + row.opens, 0),
  submissions: props.report.rows.reduce((sum, row) => sum + row.submissions, 0),
}))
</script>

<template>
  <Head title="Analytics Popups" />

  <AdminLayout>
    <AnalyticsShell>
      <template #header>
        <AnalyticsHeader
          title="Popups"
          description="Inspect popup lifecycle performance from eligibility through impression, opening, dismissal, submission, and event-based decision time."
          :filters="filters"
          :current-route="route('admin.analytics.popups.index')"
        />
      </template>
      <div class="grid gap-4 md:grid-cols-5">
        <AnalyticsKpiCard label="Tracked Popups" :value="formatNumber(report.rows.length)" />
        <AnalyticsKpiCard label="Eligible" :value="formatNumber(totals.eligible)" />
        <AnalyticsKpiCard label="Impressions" :value="formatNumber(totals.impressions)" tone="sky" />
        <AnalyticsKpiCard label="Opens" :value="formatNumber(totals.opens)" tone="amber" />
        <AnalyticsKpiCard label="Submissions" :value="formatNumber(totals.submissions)" tone="emerald" />
      </div>

      <AnalyticsDataTable :col-count="13" :show-empty="report.rows.length === 0">
        <template #description>
          Open rate is opens divided by impressions. Submission rate is submissions divided by opens. Time metrics are event-based elapsed times, and conversion-touch conversions come from attribution snapshots.
        </template>
        <template #head>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Popup</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Type</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Eligible</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Impressions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Opens</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Dismissals</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Submissions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Open Rate</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Submission Rate</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversion-Touch Conversions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Elapsed Open to Submit</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Elapsed Open to Dismiss</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Median Elapsed Open to Submit</th>
          </tr>
        </template>
        <template #empty>
          <td colspan="13" class="px-4 py-6 text-sm text-slate-600">No popup rollups found for this range.</td>
        </template>
        <tr v-for="row in report.rows" :key="row.id">
          <td class="px-4 py-3 text-sm">
            <AnalyticsMetricCell :value="row.label" :meta="row.key" />
          </td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ row.type || '—' }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.eligible) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.impressions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.opens) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.dismissals) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.submissions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700"><AnalyticsRateBadge :value="formatPercent(row.open_rate)" /></td>
          <td class="px-4 py-3 text-sm text-slate-700"><AnalyticsRateBadge :value="formatPercent(row.submission_rate)" tone="good" /></td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.conversion_touch_conversions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.avg_open_to_submit_seconds) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.avg_open_to_dismiss_seconds) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.median_open_to_submit_seconds) }}</td>
        </tr>
      </AnalyticsDataTable>
    </AnalyticsShell>
  </AdminLayout>
</template>
