<script setup lang="ts">
import AnalyticsDataTable from '@/components/admin/analytics/AnalyticsDataTable.vue'
import { formatDateTime, formatDuration, formatNumber } from '@/components/admin/analytics/formatters'

defineProps<{
  sessions: Array<{
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
}>()
</script>

<template>
  <AnalyticsDataTable :col-count="5" :show-empty="sessions.length === 0">
    <template #head>
      <tr>
        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Started</th>
        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Entry</th>
        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Scenario</th>
        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Outcome</th>
        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Journey</th>
      </tr>
    </template>

    <template #empty>
      <td colspan="5" class="px-4 py-6 text-sm text-slate-600">No sample sessions found for this range.</td>
    </template>

    <tr v-for="session in sessions" :key="session.id">
      <td class="px-4 py-3 text-sm text-slate-700">
        <div class="font-medium text-slate-900">{{ formatDateTime(session.started_at) }}</div>
        <div class="text-slate-500">{{ session.session_key }}</div>
      </td>
      <td class="px-4 py-3 text-sm text-slate-700">
        <div>{{ session.entry_page || '—' }}</div>
        <div class="text-slate-500">{{ formatNumber(session.distinct_pages) }} pages</div>
      </td>
      <td class="px-4 py-3 text-sm text-slate-700">
        <div class="font-medium text-slate-900">{{ session.primary_scenario_label || '—' }}</div>
        <div class="text-slate-500">{{ session.primary_scenario_key || 'No scenario assigned' }}</div>
        <div v-if="session.secondary_scenarios.length" class="mt-1 flex flex-wrap gap-1">
          <span
            v-for="tag in session.secondary_scenarios"
            :key="`${session.id}-${tag.scenario_key}`"
            class="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700"
          >
            {{ tag.label || tag.scenario_key }}
          </span>
        </div>
      </td>
      <td class="px-4 py-3 text-sm text-slate-700">
        <div class="font-medium" :class="session.converted ? 'text-emerald-700' : 'text-slate-900'">
          {{ session.converted ? 'Converted' : 'No conversion' }}
        </div>
        <div class="text-slate-500">
          {{ formatNumber(session.conversion_count) }} conversions / {{ formatNumber(session.event_count) }} events
        </div>
        <div class="text-slate-500">
          Event-based elapsed duration: {{ formatDuration(session.event_based_duration_seconds) }}
        </div>
      </td>
      <td class="px-4 py-3 text-sm text-slate-700">
        <div class="flex flex-wrap gap-2">
          <span
            v-for="(step, index) in session.journey_steps"
            :key="`${session.id}-${index}-${step.event_key}`"
            class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700"
          >
            {{ step.label }}
            <span v-if="step.context" class="text-slate-500">· {{ step.context }}</span>
            <span v-if="step.elapsed_from_first_event_seconds !== null" class="text-slate-500">· +{{ formatDuration(step.elapsed_from_first_event_seconds) }}</span>
          </span>
          <span
            v-if="session.truncated_events > 0"
            class="rounded-full bg-slate-900 px-3 py-1 text-xs text-white"
          >
            +{{ session.truncated_events }} more
          </span>
        </div>
      </td>
    </tr>
  </AnalyticsDataTable>
</template>
