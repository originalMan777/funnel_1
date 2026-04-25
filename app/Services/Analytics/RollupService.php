<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Conversion;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RollupService
{
    public const METRIC_PAGE_VIEWS = 'page.views';

    public const METRIC_PAGE_CONVERSIONS = 'page.conversions';

    public const METRIC_CTA_IMPRESSIONS = 'cta.impressions';

    public const METRIC_CTA_CLICKS = 'cta.clicks';

    public const METRIC_CTA_CONVERSIONS = 'cta.conversions';

    public const METRIC_LEAD_BOX_IMPRESSIONS = 'lead_box.impressions';

    public const METRIC_LEAD_BOX_CLICKS = 'lead_box.clicks';

    public const METRIC_LEAD_FORM_SUBMISSIONS = 'lead_form.submissions';

    public const METRIC_LEAD_FORM_FAILURES = 'lead_form.failures';

    public const METRIC_POPUP_ELIGIBLE = 'popup.eligible';

    public const METRIC_POPUP_IMPRESSIONS = 'popup.impressions';

    public const METRIC_POPUP_OPENS = 'popup.opens';

    public const METRIC_POPUP_DISMISSALS = 'popup.dismissals';

    public const METRIC_POPUP_SUBMISSIONS = 'popup.submissions';

    public const METRIC_CONVERSION_TOTAL = 'conversion.total';

    public const DIMENSION_TOTAL = 'total';

    public const DIMENSION_PAGE = 'page';

    public const DIMENSION_CTA = 'cta';

    public const DIMENSION_LEAD_BOX = 'lead_box';

    public const DIMENSION_POPUP = 'popup';

    public const DIMENSION_CONVERSION_TYPE = 'conversion_type';

    /**
     * Upsert daily rollup metric rows for a single date.
     *
     * @param  iterable<int, array<string, mixed>>  $metrics
     * @return Collection<int, DailyRollup>
     */
    public function upsertDailyMetrics(CarbonInterface $date, iterable $metrics): Collection
    {
        $rollups = collect();

        foreach ($metrics as $metric) {
            $rollups->push(DailyRollup::query()->updateOrCreate(
                [
                    'rollup_date' => $date->toDateString(),
                    'dimension_type' => $metric['dimension_type'],
                    'dimension_id' => $metric['dimension_id'] ?? null,
                    'metric_key' => $metric['metric_key'],
                ],
                [
                    'metric_value' => $metric['metric_value'],
                ],
            ));
        }

        return $rollups;
    }

    /**
     * Generate all managed daily rollups for a single date from analytics events and conversions.
     *
     * @return Collection<int, DailyRollup>
     */
    public function generateForDate(CarbonInterface $date): Collection
    {
        $date = $date->copy()->startOfDay();
        $metrics = $this->collectMetricsForDate($date);

        return DB::transaction(function () use ($date, $metrics): Collection {
            DailyRollup::query()
                ->whereDate('rollup_date', $date->toDateString())
                ->whereIn('metric_key', $this->managedMetricKeys())
                ->whereIn('dimension_type', $this->managedDimensionTypes())
                ->delete();

            return $this->upsertDailyMetrics($date, $metrics);
        });
    }

    /**
     * Backfill rollups across an inclusive date range.
     *
     * @return Collection<string, Collection<int, DailyRollup>>
     */
    public function backfill(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $results = collect();

        while ($cursor->lte($end)) {
            $results->put($cursor->toDateString(), $this->generateForDate($cursor));
            $cursor = $cursor->copy()->addDay();
        }

        return $results;
    }

    /**
     * @return list<array{dimension_type:string,dimension_id:int|null,metric_key:string,metric_value:int|float|string}>
     */
    private function collectMetricsForDate(CarbonInterface $date): array
    {
        return [
            ...$this->eventMetrics(
                $date,
                eventKey: 'page.view',
                metricKey: self::METRIC_PAGE_VIEWS,
                dimensionType: self::DIMENSION_PAGE,
                dimensionColumn: 'page_id',
            ),
            ...$this->conversionMetrics(
                $date,
                metricKey: self::METRIC_PAGE_CONVERSIONS,
                dimensionType: self::DIMENSION_PAGE,
                dimensionColumn: 'page_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'cta.impression',
                metricKey: self::METRIC_CTA_IMPRESSIONS,
                dimensionType: self::DIMENSION_CTA,
                dimensionColumn: 'cta_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'cta.click',
                metricKey: self::METRIC_CTA_CLICKS,
                dimensionType: self::DIMENSION_CTA,
                dimensionColumn: 'cta_id',
            ),
            ...$this->conversionMetrics(
                $date,
                metricKey: self::METRIC_CTA_CONVERSIONS,
                dimensionType: self::DIMENSION_CTA,
                dimensionColumn: 'cta_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'lead_box.impression',
                metricKey: self::METRIC_LEAD_BOX_IMPRESSIONS,
                dimensionType: self::DIMENSION_LEAD_BOX,
                dimensionColumn: 'lead_box_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'lead_box.click',
                metricKey: self::METRIC_LEAD_BOX_CLICKS,
                dimensionType: self::DIMENSION_LEAD_BOX,
                dimensionColumn: 'lead_box_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'lead_form.submitted',
                metricKey: self::METRIC_LEAD_FORM_SUBMISSIONS,
                dimensionType: self::DIMENSION_LEAD_BOX,
                dimensionColumn: 'lead_box_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'lead_form.failed',
                metricKey: self::METRIC_LEAD_FORM_FAILURES,
                dimensionType: self::DIMENSION_LEAD_BOX,
                dimensionColumn: 'lead_box_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'popup.eligible',
                metricKey: self::METRIC_POPUP_ELIGIBLE,
                dimensionType: self::DIMENSION_POPUP,
                dimensionColumn: 'popup_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'popup.impression',
                metricKey: self::METRIC_POPUP_IMPRESSIONS,
                dimensionType: self::DIMENSION_POPUP,
                dimensionColumn: 'popup_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'popup.opened',
                metricKey: self::METRIC_POPUP_OPENS,
                dimensionType: self::DIMENSION_POPUP,
                dimensionColumn: 'popup_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'popup.dismissed',
                metricKey: self::METRIC_POPUP_DISMISSALS,
                dimensionType: self::DIMENSION_POPUP,
                dimensionColumn: 'popup_id',
            ),
            ...$this->eventMetrics(
                $date,
                eventKey: 'popup.submitted',
                metricKey: self::METRIC_POPUP_SUBMISSIONS,
                dimensionType: self::DIMENSION_POPUP,
                dimensionColumn: 'popup_id',
            ),
            ...$this->conversionTotals($date),
            ...$this->conversionMetrics(
                $date,
                metricKey: self::METRIC_CONVERSION_TOTAL,
                dimensionType: self::DIMENSION_CONVERSION_TYPE,
                dimensionColumn: 'conversion_type_id',
            ),
        ];
    }

    /**
     * @return list<array{dimension_type:string,dimension_id:int|null,metric_key:string,metric_value:int}>
     */
    private function eventMetrics(
        CarbonInterface $date,
        string $eventKey,
        string $metricKey,
        string $dimensionType,
        string $dimensionColumn,
    ): array {
        return Event::query()
            ->join('analytics_event_types', 'analytics_event_types.id', '=', 'analytics_events.event_type_id')
            ->where('analytics_event_types.event_key', $eventKey)
            ->whereDate('analytics_events.occurred_at', $date->toDateString())
            ->whereNotNull("analytics_events.{$dimensionColumn}")
            ->groupBy("analytics_events.{$dimensionColumn}")
            ->selectRaw("analytics_events.{$dimensionColumn} as dimension_id, COUNT(*) as aggregate_count")
            ->get()
            ->map(fn ($row) => [
                'dimension_type' => $dimensionType,
                'dimension_id' => (int) $row->dimension_id,
                'metric_key' => $metricKey,
                'metric_value' => (int) $row->aggregate_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{dimension_type:string,dimension_id:int|null,metric_key:string,metric_value:int}>
     */
    private function conversionMetrics(
        CarbonInterface $date,
        string $metricKey,
        string $dimensionType,
        string $dimensionColumn,
    ): array {
        return Conversion::query()
            ->whereDate('occurred_at', $date->toDateString())
            ->whereNotNull($dimensionColumn)
            ->groupBy($dimensionColumn)
            ->selectRaw("{$dimensionColumn} as dimension_id, COUNT(*) as aggregate_count")
            ->get()
            ->map(fn ($row) => [
                'dimension_type' => $dimensionType,
                'dimension_id' => (int) $row->dimension_id,
                'metric_key' => $metricKey,
                'metric_value' => (int) $row->aggregate_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{dimension_type:string,dimension_id:int|null,metric_key:string,metric_value:int}>
     */
    private function conversionTotals(CarbonInterface $date): array
    {
        $count = Conversion::query()
            ->whereDate('occurred_at', $date->toDateString())
            ->count();

        return [[
            'dimension_type' => self::DIMENSION_TOTAL,
            'dimension_id' => null,
            'metric_key' => self::METRIC_CONVERSION_TOTAL,
            'metric_value' => $count,
        ]];
    }

    /**
     * @return list<string>
     */
    public function managedMetricKeys(): array
    {
        return [
            self::METRIC_PAGE_VIEWS,
            self::METRIC_PAGE_CONVERSIONS,
            self::METRIC_CTA_IMPRESSIONS,
            self::METRIC_CTA_CLICKS,
            self::METRIC_CTA_CONVERSIONS,
            self::METRIC_LEAD_BOX_IMPRESSIONS,
            self::METRIC_LEAD_BOX_CLICKS,
            self::METRIC_LEAD_FORM_SUBMISSIONS,
            self::METRIC_LEAD_FORM_FAILURES,
            self::METRIC_POPUP_ELIGIBLE,
            self::METRIC_POPUP_IMPRESSIONS,
            self::METRIC_POPUP_OPENS,
            self::METRIC_POPUP_DISMISSALS,
            self::METRIC_POPUP_SUBMISSIONS,
            self::METRIC_CONVERSION_TOTAL,
        ];
    }

    /**
     * @return list<string>
     */
    public function managedDimensionTypes(): array
    {
        return [
            self::DIMENSION_TOTAL,
            self::DIMENSION_PAGE,
            self::DIMENSION_CTA,
            self::DIMENSION_LEAD_BOX,
            self::DIMENSION_POPUP,
            self::DIMENSION_CONVERSION_TYPE,
        ];
    }
}
