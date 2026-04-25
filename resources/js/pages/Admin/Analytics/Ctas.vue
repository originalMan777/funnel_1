<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/AppLayouts/AdminLayout.vue'
import AnalyticsBarComparison from '@/components/admin/analytics/AnalyticsBarComparison.vue'
import AnalyticsDataTable from '@/components/admin/analytics/AnalyticsDataTable.vue'
import AnalyticsHeader from '@/components/admin/analytics/AnalyticsHeader.vue'
import AnalyticsKpiCard from '@/components/admin/analytics/AnalyticsKpiCard.vue'
import AnalyticsMetricCell from '@/components/admin/analytics/AnalyticsMetricCell.vue'
import AnalyticsRateBadge from '@/components/admin/analytics/AnalyticsRateBadge.vue'
import AnalyticsShell from '@/components/admin/analytics/AnalyticsShell.vue'
import AnalyticsTrendChart from '@/components/admin/analytics/AnalyticsTrendChart.vue'
import { formatDuration, formatNumber, formatPercent } from '@/components/admin/analytics/formatters'

type CtaRow = {
  id: number
  key: string
  label: string
  intent_key: string | null
  impressions: number
  clicks: number
  conversions: number
  ctr: number | null
  conversion_rate: number | null
  avg_time_to_click_seconds: number | null
  avg_click_to_conversion_seconds: number | null
  median_click_to_conversion_seconds: number | null
  conversion_touch_conversions: number
}

type FeaturedMetricGroup = {
  id: string
  title: string
  detail: string
  row: CtaRow
  metrics: Array<{ label: string; value: string }>
}

const props = defineProps<{
  filters: {
    from: string
    to: string
    presets: Array<{ label: string; days: number }>
  }
  report: {
    rows: CtaRow[]
    trend: Array<{
      date: string
      impressions: number
      clicks: number
      conversions: number
    }>
  }
}>()

const rows = computed(() => props.report.rows)

const totals = computed(() => ({
  impressions: rows.value.reduce((sum, row) => sum + row.impressions, 0),
  clicks: rows.value.reduce((sum, row) => sum + row.clicks, 0),
  conversions: rows.value.reduce((sum, row) => sum + row.conversions, 0),
  conversionTouchConversions: rows.value.reduce(
    (sum, row) => sum + row.conversion_touch_conversions,
    0,
  ),
}))

const aggregateCtr = computed(() => {
  if (totals.value.impressions <= 0) {
    return null
  }

  return (totals.value.clicks / totals.value.impressions) * 100
})

const aggregateConversionRate = computed(() => {
  if (totals.value.clicks <= 0) {
    return null
  }

  return (totals.value.conversions / totals.value.clicks) * 100
})

const weightedAverageTimeToClick = computed(() => {
  const weightedRows = rows.value.filter(
    (row) => row.avg_time_to_click_seconds !== null && row.clicks > 0,
  )
  const totalWeight = weightedRows.reduce((sum, row) => sum + row.clicks, 0)

  if (totalWeight <= 0) {
    return null
  }

  return (
    weightedRows.reduce(
      (sum, row) => sum + row.clicks * Number(row.avg_time_to_click_seconds ?? 0),
      0,
    ) / totalWeight
  )
})

const highestClickVolumeCta = computed(
  () =>
    [...rows.value].sort(
      (a, b) => b.clicks - a.clicks || b.impressions - a.impressions,
    )[0] ?? null,
)

const topPerformingCta = computed(
  () =>
    [...rows.value]
      .filter((row) => row.clicks > 0 && row.conversion_rate !== null)
      .sort((a, b) => {
        const rateDelta = Number(b.conversion_rate ?? -1) - Number(a.conversion_rate ?? -1)

        if (rateDelta !== 0) {
          return rateDelta
        }

        return b.conversions - a.conversions || b.clicks - a.clicks
      })[0] ?? null,
)

const weakFollowThroughCta = computed(
  () =>
    [...rows.value]
      .filter(
        (row) =>
          row.clicks > 0 &&
          row.conversion_rate !== null &&
          (aggregateConversionRate.value === null || row.conversion_rate < aggregateConversionRate.value),
      )
      .sort(
        (a, b) =>
          b.clicks - a.clicks ||
          Number(a.conversion_rate ?? 101) - Number(b.conversion_rate ?? 101),
      )[0] ?? null,
)

const topComparisonRows = computed(() =>
  [...rows.value]
    .filter((row) => row.clicks > 0 && row.conversion_rate !== null)
    .sort((a, b) => {
      const rateDelta = Number(b.conversion_rate ?? -1) - Number(a.conversion_rate ?? -1)

      if (rateDelta !== 0) {
        return rateDelta
      }

      return b.conversions - a.conversions || b.clicks - a.clicks
    })
    .slice(0, 5)
    .map((row) => ({
      label: row.label,
      value: Math.round(Number(row.conversion_rate ?? 0)),
      context: `${formatNumber(row.clicks)} clicks • ${formatNumber(row.conversions)} conversions`,
    })),
)

const weakestComparisonRows = computed(() =>
  [...rows.value]
    .filter((row) => row.clicks > 0 && row.conversion_rate !== null)
    .sort((a, b) => {
      const rateDelta = Number(a.conversion_rate ?? 101) - Number(b.conversion_rate ?? 101)

      if (rateDelta !== 0) {
        return rateDelta
      }

      return b.clicks - a.clicks || a.conversions - b.conversions
    })
    .slice(0, 5)
    .map((row) => ({
      label: row.label,
      value: Math.round(Number(row.conversion_rate ?? 0)),
      context: `${formatNumber(row.clicks)} clicks • ${formatPercent(row.ctr)} CTR`,
    })),
)

const summaryText = computed(() => {
  const lines: string[] = []

  if (highestClickVolumeCta.value) {
    lines.push(
      `CTA activity is led by ${highestClickVolumeCta.value.label} with ${formatNumber(highestClickVolumeCta.value.clicks)} clicks.`,
    )
  }

  if (topPerformingCta.value) {
    lines.push(
      `The strongest conversion signal is ${topPerformingCta.value.label} at ${formatPercent(topPerformingCta.value.conversion_rate)} conversion rate.`,
    )
  }

  if (weakFollowThroughCta.value) {
    lines.push(
      `Weak follow-through appears in ${weakFollowThroughCta.value.label}, which is still drawing ${formatNumber(weakFollowThroughCta.value.clicks)} clicks while trailing the overall CTA conversion rate.`,
    )
  }

  if (lines.length === 0) {
    return 'CTA rollups are available for this range, but there is not yet enough click-backed variance to call out a leading or lagging CTA confidently.'
  }

  return lines.join(' ')
})

const summaryEvidence = computed(() => [
  {
    label: 'traffic cluster',
    value: 'Traffic / CTAs',
  },
  {
    label: 'tracked ctas',
    value: formatNumber(rows.value.length),
  },
  {
    label: 'conversion-touch support',
    value: formatNumber(totals.value.conversionTouchConversions),
  },
])

const featuredMetricGroups = computed<FeaturedMetricGroup[]>(() => {
  const candidates = [
    highestClickVolumeCta.value
      ? {
          id: 'top-click-volume',
          title: 'Top by Clicks',
          detail: `${highestClickVolumeCta.value.label} currently leads CTA click volume in this Traffic / CTAs sub-cluster.`,
          row: highestClickVolumeCta.value,
          metrics: [
            {
              label: 'Clicks',
              value: formatNumber(highestClickVolumeCta.value.clicks),
            },
            {
              label: 'CTR',
              value: formatPercent(highestClickVolumeCta.value.ctr),
            },
            {
              label: 'Conversions',
              value: formatNumber(highestClickVolumeCta.value.conversions),
            },
          ],
        }
      : null,
    topPerformingCta.value
      ? {
          id: 'top-conversion-rate',
          title: 'Top by Conversion Rate',
          detail: `${topPerformingCta.value.label} is turning click intent into conversions more efficiently than the rest of the CTA set.`,
          row: topPerformingCta.value,
          metrics: [
            {
              label: 'Conversion Rate',
              value: formatPercent(topPerformingCta.value.conversion_rate),
            },
            {
              label: 'Conversions',
              value: formatNumber(topPerformingCta.value.conversions),
            },
            {
              label: 'Median Click to Conversion',
              value: formatDuration(topPerformingCta.value.median_click_to_conversion_seconds),
            },
          ],
        }
      : null,
    weakFollowThroughCta.value
      ? {
          id: 'weak-follow-through',
          title: 'Weakest Follow-Through',
          detail: `${weakFollowThroughCta.value.label} has meaningful click volume but is under the sub-cluster conversion benchmark.`,
          row: weakFollowThroughCta.value,
          metrics: [
            {
              label: 'Clicks',
              value: formatNumber(weakFollowThroughCta.value.clicks),
            },
            {
              label: 'Conversion Rate',
              value: formatPercent(weakFollowThroughCta.value.conversion_rate),
            },
            {
              label: 'Conversion-Touch Conversions',
              value: formatNumber(weakFollowThroughCta.value.conversion_touch_conversions),
            },
          ],
        }
      : null,
  ].filter((group): group is FeaturedMetricGroup => group !== null)

  const seen = new Set<number>()

  return candidates.filter((group) => {
    if (seen.has(group.row.id)) {
      return false
    }

    seen.add(group.row.id)

    return true
  })
})

const comparisonEmptyState = computed(
  () =>
    topComparisonRows.value.length === 0 && weakestComparisonRows.value.length === 0,
)
</script>

<template>
  <Head title="Analytics CTAs" />

  <AdminLayout>
    <AnalyticsShell>
      <template #header>
        <AnalyticsHeader
          title="CTAs"
          description="Traffic / CTAs. Review CTA visibility, click activity, downstream conversion follow-through, and timing signals using the current CTA sub-cluster analytics report."
          :filters="filters"
          :current-route="route('admin.analytics.ctas.index')"
        />
      </template>

      <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/50">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
          <div class="max-w-3xl">
            <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
              Sub-Cluster Summary
            </p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
              Traffic / CTAs
            </h2>
            <p class="mt-3 text-sm leading-6 text-slate-700">
              {{ summaryText }}
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[25rem]">
            <div
              v-for="item in summaryEvidence"
              :key="item.label"
              class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
            >
              <p class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase">
                {{ item.label }}
              </p>
              <p class="mt-2 text-sm font-semibold text-slate-900">
                {{ item.value }}
              </p>
            </div>
          </div>
        </div>
      </section>

      <section class="grid gap-4 xl:grid-cols-4">
        <AnalyticsKpiCard
          label="Total CTA Clicks"
          :value="formatNumber(totals.clicks)"
          hint="All CTA clicks captured in the selected range."
          tone="amber"
        />
        <AnalyticsKpiCard
          label="Overall CTA CTR"
          :value="formatPercent(aggregateCtr)"
          hint="Clicks divided by impressions across the full CTA set."
          tone="sky"
        />
        <AnalyticsKpiCard
          label="Overall CTA Conversion Rate"
          :value="formatPercent(aggregateConversionRate)"
          hint="CTA-linked conversions divided by CTA clicks."
          tone="emerald"
        />
        <AnalyticsKpiCard
          label="Average Time to Click"
          :value="formatDuration(weightedAverageTimeToClick)"
          hint="Weighted by CTA click volume where event-based timing exists."
        />
      </section>

      <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
        <div>
          <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
            Featured Metric Groups
          </p>
          <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">
            Individual CTAs worth review first
          </h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">
            Each card below treats an individual CTA as a metric group inside the Traffic / CTAs sub-cluster. Detail routes are intentionally held back for now.
          </p>
        </div>

        <div v-if="featuredMetricGroups.length > 0" class="mt-5 grid gap-4 xl:grid-cols-3">
          <article
            v-for="group in featuredMetricGroups"
            :key="group.id"
            class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5"
            aria-disabled="true"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase">
                  Metric Group
                </p>
                <h3 class="mt-2 text-lg font-semibold text-slate-950">
                  {{ group.title }}
                </h3>
              </div>

              <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">
                CTA
              </span>
            </div>

            <div class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-white/80 px-4 py-3 text-xs text-slate-500">
              <span class="font-medium text-slate-700">Traffic / CTAs</span>
              <span> / </span>
              <span class="font-medium text-slate-700">{{ group.row.label }}</span>
            </div>

            <div class="mt-4">
              <p class="text-sm font-semibold text-slate-900">{{ group.row.label }}</p>
              <p class="mt-1 text-xs text-slate-500">
                {{ group.row.intent_key || group.row.key }}
              </p>
              <p class="mt-3 text-sm leading-6 text-slate-600">
                {{ group.detail }}
              </p>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
              <div
                v-for="metric in group.metrics"
                :key="`${group.id}-${metric.label}`"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3"
              >
                <div class="text-xs font-medium text-slate-500">{{ metric.label }}</div>
                <div class="mt-2 text-lg font-semibold text-slate-950">{{ metric.value }}</div>
              </div>
            </div>

            <div class="mt-5 border-t border-slate-200 pt-4 text-sm text-slate-500">
              Detail view coming later.
            </div>
          </article>
        </div>

        <div
          v-else
          class="mt-5 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500"
        >
          No featured CTA metric groups are available until rollup-backed CTA activity exists for the selected range.
        </div>
      </section>

      <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
        <div>
          <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
            Primary Comparison
          </p>
          <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">
            Which CTA metric groups are strongest or weakest?
          </h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">
            This is the main comparison view for the sub-cluster: which individual CTAs turn clicks into conversions most effectively, and which ones underperform once users engage.
          </p>
        </div>

        <div v-if="comparisonEmptyState" class="mt-5 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">
          There is not enough click-backed CTA data in this range to compare conversion follow-through yet.
        </div>

        <div v-else class="mt-5 grid gap-4 xl:grid-cols-2">
          <div class="rounded-[1.5rem] bg-slate-50 p-5">
            <p class="text-sm font-medium text-slate-900">Best converting CTAs</p>
            <div class="mt-3">
              <AnalyticsBarComparison :rows="topComparisonRows" />
            </div>
          </div>

          <div class="rounded-[1.5rem] bg-slate-50 p-5">
            <p class="text-sm font-medium text-slate-900">Weakest follow-through</p>
            <div class="mt-3">
              <AnalyticsBarComparison :rows="weakestComparisonRows" />
            </div>
          </div>
        </div>
      </section>

      <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
        <div>
          <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
            Secondary Trend
          </p>
          <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">
            How CTA performance is changing over time
          </h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">
            This trend view uses the existing CTA time series to show how impressions, clicks, and CTA-linked conversions move across the selected date range.
          </p>
        </div>

        <div class="mt-5">
          <AnalyticsTrendChart
            :rows="report.trend"
            :series="[
              {
                key: 'impressions',
                label: 'Impressions',
                colorClass: 'bg-slate-300',
              },
              {
                key: 'clicks',
                label: 'Clicks',
                colorClass: 'bg-amber-500',
              },
              {
                key: 'conversions',
                label: 'Conversions',
                colorClass: 'bg-slate-900',
              },
            ]"
          />
        </div>
      </section>

      <AnalyticsDataTable :col-count="11" :show-empty="report.rows.length === 0">
        <template #description>
          <div>
            <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
              Metric Group Table
            </p>
            <p class="mt-2 text-sm leading-6 text-slate-600">
              The full CTA table remains the proof layer for the Traffic / CTAs sub-cluster. CTR is clicks divided by impressions. Conversion rate is conversions divided by clicks. Time metrics are event-based elapsed times, and conversion-touch conversions come from attribution snapshots.
            </p>
          </div>
        </template>
        <template #head>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">CTA</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Intent</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Impressions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Clicks</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">CTR</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversion Rate</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Conversion-Touch Conversions</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Elapsed Page to Click</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average Elapsed Click to Conversion</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Median Elapsed Click to Conversion</th>
          </tr>
        </template>
        <template #empty>
          <td colspan="11" class="px-4 py-6 text-sm text-slate-600">No CTA rollups found for this range.</td>
        </template>
        <tr v-for="row in report.rows" :key="row.id">
          <td class="px-4 py-3 text-sm">
            <AnalyticsMetricCell :value="row.label" :meta="row.key" />
          </td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ row.intent_key || '—' }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.impressions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.clicks) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.conversions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700"><AnalyticsRateBadge :value="formatPercent(row.ctr)" /></td>
          <td class="px-4 py-3 text-sm text-slate-700"><AnalyticsRateBadge :value="formatPercent(row.conversion_rate)" tone="good" /></td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatNumber(row.conversion_touch_conversions) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.avg_time_to_click_seconds) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.avg_click_to_conversion_seconds) }}</td>
          <td class="px-4 py-3 text-sm text-slate-700">{{ formatDuration(row.median_click_to_conversion_seconds) }}</td>
        </tr>
      </AnalyticsDataTable>
    </AnalyticsShell>
  </AdminLayout>
</template>
