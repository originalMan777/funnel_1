<script setup lang="ts">
import { formatDuration, formatNumber } from '@/components/admin/analytics/formatters'

defineProps<{
  funnel: {
    key: string
    label: string
    description: string
    conversion_count: number
    average_elapsed_seconds: number | null
    dismissed_without_submit?: number
    step_timings?: Array<{
      key: string
      label: string
      average_elapsed_seconds: number | null
    }>
    special_timings?: Array<{
      key: string
      label: string
      average_elapsed_seconds: number | null
    }>
    top_drop_off?: {
      label: string
      count: number
      drop_off_to_next: number
    } | null
    steps: Array<{
      key: string
      label: string
      count: number
      drop_off_to_next: number
    }>
  }
}>()
</script>

<template>
  <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/50">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">{{ funnel.label }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ funnel.description }}</p>
      </div>
      <div class="text-right">
        <p class="text-sm text-slate-500">Conversions</p>
        <p class="text-2xl font-semibold text-slate-900">{{ formatNumber(funnel.conversion_count) }}</p>
        <p class="mt-1 text-sm text-slate-500">Average elapsed {{ formatDuration(funnel.average_elapsed_seconds) }}</p>
      </div>
    </div>

    <div class="mt-5 space-y-3">
      <div
        v-for="step in funnel.steps"
        :key="step.key"
        class="rounded-2xl bg-slate-50 p-4"
      >
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="font-medium text-slate-900">{{ step.label }}</p>
            <p class="text-sm text-slate-500">Sessions reaching this step</p>
          </div>
          <div class="text-right text-sm text-slate-700">
            <p>{{ formatNumber(step.count) }}</p>
            <p>Drop-off to next: {{ formatNumber(step.drop_off_to_next) }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
      <div class="rounded-2xl border border-slate-100 p-4">
        <p class="text-sm font-medium text-slate-900">Top drop-off</p>
        <p class="mt-2 text-sm text-slate-600" v-if="funnel.top_drop_off">
          {{ funnel.top_drop_off.label }} lost {{ formatNumber(funnel.top_drop_off.drop_off_to_next) }} sessions before the next step.
        </p>
        <p v-else class="mt-2 text-sm text-slate-500">No measurable drop-off yet.</p>
      </div>
      <div class="rounded-2xl border border-slate-100 p-4">
        <p class="text-sm font-medium text-slate-900">Special outcome</p>
        <p class="mt-2 text-sm text-slate-600" v-if="funnel.dismissed_without_submit !== undefined">
          Popup dismissed without submit: {{ formatNumber(funnel.dismissed_without_submit) }}
        </p>
        <p v-else class="mt-2 text-sm text-slate-500">No extra outcome tracked for this funnel.</p>
      </div>
    </div>

    <div v-if="(funnel.step_timings?.length || 0) > 0 || (funnel.special_timings?.length || 0) > 0" class="mt-5 rounded-2xl border border-slate-100 p-4">
      <p class="text-sm font-medium text-slate-900">Event-Based Step Timing</p>
      <div class="mt-3 grid gap-3 md:grid-cols-2">
        <div
          v-for="timing in [...(funnel.step_timings || []), ...(funnel.special_timings || [])]"
          :key="timing.key"
          class="rounded-2xl bg-slate-50 p-3"
        >
          <p class="text-sm text-slate-700">{{ timing.label }}</p>
          <p class="mt-1 text-sm font-medium text-slate-900">{{ formatDuration(timing.average_elapsed_seconds) }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
