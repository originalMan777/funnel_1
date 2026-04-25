<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/AppLayouts/AdminLayout.vue'
import AnalyticsDataTable from '@/components/admin/analytics/AnalyticsDataTable.vue'
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue'
import AnalyticsKpiCard from '@/components/admin/analytics/AnalyticsKpiCard.vue'
import AnalyticsRateBadge from '@/components/admin/analytics/AnalyticsRateBadge.vue'
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue'
import SessionJourneyTable from '@/components/admin/analytics/SessionJourneyTable.vue'
import { formatDuration, formatNumber, formatPercent } from '@/components/admin/analytics/formatters'

const props = defineProps<{
  filters: {
    from: string
    to: string
    presets: Array<{ label: string; days: number }>
  }
  report: {
    selected_scenario: string | null
    rows: Array<{
      scenario_key: string
      label: string
      description: string | null
      sessions: number
      converted_sessions: number
      conversion_total: number
      conversion_rate: number | null
      average_events: number
      average_session_duration_seconds: number | null
      median_session_duration_seconds: number | null
    }>
    secondary_rows: Array<{
      scenario_key: string
      label: string
      description: string | null
      sessions: number
      converted_sessions: number
      conversion_rate: number | null
    }>
    sample_sessions: Array<{
      id: number
      session_key: string
      started_at: string | null
      ended_at: string | null
      entry_page: string | null
      converted: boolean
      conversion_count: number
      event_count: number
      event_based_duration_seconds: number | null
      distinct_pages: number
      primary_scenario_key: string | null
      primary_scenario_label: string | null
      secondary_scenarios: Array<{
        scenario_key: string | null
        label: string | null
      }>
      journey_steps: Array<{
        event_key: string | null
        label: string | null
        context: string | null
        occurred_at: string | null
        elapsed_from_first_event_seconds: number | null
      }>
      truncated_events: number
    }>
  }
}>()

const totals = computed(() => ({
  primaryScenarios: props.report.rows.length,
  totalSessions: props.report.rows.reduce((sum, row) => sum + row.sessions, 0),
  convertedSessions: props.report.rows.reduce((sum, row) => sum + row.converted_sessions, 0),
  secondaryTags: props.report.secondary_rows.length,
}))
</script>

<template>
  <Head title="Analytics Scenarios" />

  <AdminLayout>
    <AnalyticsShell>
      <template #header>
        <AnalyticsHeader
          title="Scenarios"
          description="Review explicit, rule-based session scenario assignments built from analytics sessions, events, and conversions. Use them to compare path types and inspect sample journeys."
          :filters="filters"
          :current-route="route('admin.analytics.scenarios.index')"
        />
      </template>

      <div class="grid gap-4 md:grid-cols-4">
        <AnalyticsKpiCard label="Primary Scenarios" :value="formatNumber(totals.primaryScenarios)" />
        <AnalyticsKpiCard label="Scenario Sessions" :value="formatNumber(totals.totalSessions)" tone="sky" />
        <AnalyticsKpiCard label="Converted Sessions" :value="formatNumber(totals.convertedSessions)" tone="emerald" />
        <AnalyticsKpiCard label="Secondary Tags" :value="formatNumber(totals.secondaryTags)" />
      </div>

      <AnalyticsDataTable :col-count="9" :show-empty="report.rows.length === 0">
        <template #description>
          One primary scenario is assigned per session. Secondary tags are only added when the event pattern clearly supports them. Duration values are event-based elapsed time.
        </template>
        <template #head>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Scenario</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Sessions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Converted Sessions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversion Rate</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Events per Session</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Event-Based Session Duration</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Median Event-Based Session Duration</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Sessions</th>
          </tr>
        </template>
        <template #empty>
          <td colspan="9" class="px-4 py-6 text-sm text-slate-600">No scenario assignments found for this range.</td>
        </template>
        <tr v-for="row in report.rows" :key="row.scenario_key">
          <td class="px-4 py-3 text-sm text-slate-700">
            <div class="font-medium text-slate-900">{{ row.label }}</div>
            <div class="text-slate-500">{{ row.description || row.scenario_key }}</div>
          </td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.sessions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.converted_sessions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.conversion_total) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700"><AnalyticsRateBadge :value="formatPercent(row.conversion_rate)" tone="good" /></td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ row.average_events }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.average_session_duration_seconds) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.median_session_duration_seconds) }}</td>
          <td class="px-4 py-3 text-sm">
            <Link
              :href="route('admin.analytics.scenarios.index', { from: filters.from, to: filters.to, scenario: row.scenario_key })"
              class="rounded-xl border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50"
              preserve-state
              preserve-scroll
            >
              {{ report.selected_scenario === row.scenario_key ? 'Viewing' : 'View sessions' }}
            </Link>
          </td>
        </tr>
      </AnalyticsDataTable>

      <AnalyticsDataTable :col-count="4" :show-empty="report.secondary_rows.length === 0">
        <template #description>
          Secondary scenario tags help show supporting patterns without replacing the primary session scenario.
        </template>
        <template #head>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Secondary Tag</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Sessions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Converted Sessions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversion Rate</th>
          </tr>
        </template>
        <template #empty>
          <td colspan="4" class="px-4 py-6 text-sm text-slate-600">No secondary scenario tags found for this range.</td>
        </template>
        <tr v-for="row in report.secondary_rows" :key="row.scenario_key">
          <td class="px-4 py-3 text-sm text-slate-700">
            <div class="font-medium text-slate-900">{{ row.label }}</div>
            <div class="text-slate-500">{{ row.description || row.scenario_key }}</div>
          </td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.sessions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.converted_sessions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700"><AnalyticsRateBadge :value="formatPercent(row.conversion_rate)" /></td>
        </tr>
      </AnalyticsDataTable>

      <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/50">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Sample Session Journeys</h2>
            <p class="mt-1 text-sm text-slate-600">
              Showing recent sessions for
              <span class="font-medium text-slate-900">{{ report.selected_scenario || 'the current scenario view' }}</span>.
            </p>
          </div>
        </div>
        <div class="mt-4">
          <SessionJourneyTable :sessions="report.sample_sessions" />
        </div>
      </section>
    </AnalyticsShell>
  </AdminLayout>
</template>
