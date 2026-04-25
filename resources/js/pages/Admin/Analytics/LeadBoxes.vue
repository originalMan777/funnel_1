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
      impressions: number
      clicks: number
      submissions: number
      failures: number
      click_through_rate: number | null
      submission_rate: number | null
      avg_impression_to_submit_seconds: number | null
      avg_click_to_submit_seconds: number | null
    }>
  }
}>()

const totals = computed(() => ({
  impressions: props.report.rows.reduce((sum, row) => sum + row.impressions, 0),
  submissions: props.report.rows.reduce((sum, row) => sum + row.submissions, 0),
  failures: props.report.rows.reduce((sum, row) => sum + row.failures, 0),
}))
</script>

<template>
  <Head title="Analytics Lead Boxes" />

  <AdminLayout>
    <AnalyticsShell>
      <template #header>
        <AnalyticsHeader
          title="Lead Boxes"
          description="Compare first-wave lead capture surfaces by exposure, engagement, submissions, and event-based elapsed time into form completion."
          :filters="filters"
          :current-route="route('admin.analytics.lead-boxes.index')"
        />
      </template>
      <div class="grid gap-4 md:grid-cols-4">
        <AnalyticsKpiCard label="Tracked Lead Boxes" :value="formatNumber(report.rows.length)" />
        <AnalyticsKpiCard label="Impressions" :value="formatNumber(totals.impressions)" tone="sky" />
        <AnalyticsKpiCard label="Submissions" :value="formatNumber(totals.submissions)" tone="emerald" />
        <AnalyticsKpiCard label="Failures" :value="formatNumber(totals.failures)" tone="amber" />
      </div>

      <AnalyticsDataTable :col-count="10" :show-empty="report.rows.length === 0">
        <template #description>
          Click-through rate is clicks divided by impressions. Submission rate is submissions divided by clicks. Time metrics are event-based elapsed times.
        </template>
        <template #head>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Lead Box</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Type</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Impressions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Clicks</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Submissions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Failures</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Click-Through</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Submission Rate</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Elapsed Impression to Submit</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Elapsed Click to Submit</th>
          </tr>
        </template>
        <template #empty>
          <td colspan="10" class="px-4 py-6 text-sm text-slate-600">No lead-box rollups found for this range.</td>
        </template>
        <tr v-for="row in report.rows" :key="row.id">
          <td class="px-4 py-3 text-sm">
            <AnalyticsMetricCell :value="row.label" :meta="row.key" />
          </td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ row.type || '—' }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.impressions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.clicks) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.submissions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.failures) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700"><AnalyticsRateBadge :value="formatPercent(row.click_through_rate)" /></td>
          <td class="px-4 py-3 text-sm text-slate-700"><AnalyticsRateBadge :value="formatPercent(row.submission_rate)" tone="good" /></td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.avg_impression_to_submit_seconds) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.avg_click_to_submit_seconds) }}</td>
        </tr>
      </AnalyticsDataTable>
    </AnalyticsShell>
  </AdminLayout>
</template>
