<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/AppLayouts/AdminLayout.vue'
import AnalyticsDataTable from '@/components/admin/analytics/AnalyticsDataTable.vue'
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue'
import AnalyticsKpiCard from '@/components/admin/analytics/AnalyticsKpiCard.vue'
import AnalyticsMetricCell from '@/components/admin/analytics/AnalyticsMetricCell.vue'
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue'
import { formatNumber } from '@/components/admin/analytics/formatters'

defineProps<{
  filters: {
    from: string
    to: string
    presets: Array<{ label: string; days: number }>
  }
  report: {
    overview: {
      attributed_conversions: number
      unattributed_conversions: number
    }
    first_touch: Array<{
      source_key: string | null
      source_label: string
      attribution_method: string
      conversion_count: number
    }>
    last_touch: Array<{
      source_key: string | null
      source_label: string
      attribution_method: string
      conversion_count: number
    }>
    conversion_touch: Array<{
      source_key: string | null
      source_label: string
      attribution_method: string
      conversion_count: number
    }>
  }
}>()

const scopes = [
  { key: 'first_touch', label: 'First-Touch' },
  { key: 'last_touch', label: 'Last-Touch' },
  { key: 'conversion_touch', label: 'Conversion-Touch' },
] as const
</script>

<template>
  <Head title="Analytics Attribution" />

  <AdminLayout>
    <AnalyticsShell>
      <template #header>
        <AnalyticsHeader
          title="Attribution"
          description="Review explicit attribution snapshots derived from observed touches, session entry fallbacks, and conversion-touch events. These are touch-based association summaries, not claims of full causality."
          :filters="filters"
          :current-route="route('admin.analytics.attribution.index')"
        />
      </template>
      <div class="grid gap-4 md:grid-cols-2">
        <AnalyticsKpiCard label="Attributed Conversions" :value="formatNumber(report.overview.attributed_conversions)" tone="emerald" />
        <AnalyticsKpiCard label="Unattributed Conversions" :value="formatNumber(report.overview.unattributed_conversions)" tone="amber" />
      </div>

      <AnalyticsDataTable
        v-for="scope in scopes"
        :key="scope.key"
        :col-count="3"
        :show-empty="report[scope.key].length === 0"
      >
        <template #description>
          <h2 class="text-lg font-semibold text-slate-900">{{ scope.label }}</h2>
          <p class="mt-1 text-sm text-slate-600">Top sources for {{ scope.label.toLowerCase() }} attribution in the selected range.</p>
        </template>
        <template #head>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Source</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Method</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversions</th>
          </tr>
        </template>
        <template #empty>
          <td colspan="3" class="px-4 py-6 text-sm text-slate-600">No attribution rows found for this scope and range.</td>
        </template>
        <tr v-for="row in report[scope.key]" :key="`${scope.key}-${row.source_key}-${row.attribution_method}`">
          <td class="px-4 py-3 text-sm">
            <AnalyticsMetricCell :value="row.source_label" :meta="row.source_key || 'unknown'" />
          </td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ row.attribution_method }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.conversion_count) }}</td>
        </tr>
      </AnalyticsDataTable>
    </AnalyticsShell>
  </AdminLayout>
</template>
