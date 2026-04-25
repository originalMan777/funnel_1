<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/AppLayouts/AdminLayout.vue'
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue'
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue'
import FunnelSummaryCard from '@/components/admin/analytics/FunnelSummaryCard.vue'

defineProps<{
  filters: {
    from: string
    to: string
    presets: Array<{ label: string; days: number }>
  }
  report: {
    funnels: Array<{
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
    }>
  }
}>()
</script>

<template>
  <Head title="Analytics Funnels" />

  <AdminLayout>
    <AnalyticsShell>
      <template #header>
        <AnalyticsHeader
          title="Funnels"
          description="Review the supported session-based funnels that the current analytics event stream can reconstruct honestly, including step counts, drop-off, and end-of-funnel conversion totals."
          :filters="filters"
          :current-route="route('admin.analytics.funnels.index')"
        />
      </template>
      <div class="grid gap-4">
        <FunnelSummaryCard
          v-for="funnel in report.funnels"
          :key="funnel.key"
          :funnel="funnel"
        />
      </div>
    </AnalyticsShell>
  </AdminLayout>
</template>
