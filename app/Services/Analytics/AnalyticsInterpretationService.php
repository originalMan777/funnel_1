<?php

namespace App\Services\Analytics;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AnalyticsInterpretationService
{
    public function __construct(
        private readonly AnalyticsFunnelService $funnelService,
        private readonly AnalyticsReportService $analyticsReportService,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function summarize(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $funnels = $this->funnelService->analyze($from, $to);
        $scenarios = $this->analyticsReportService->scenarioPerformance($from, $to);
        $secondaryScenarios = $this->analyticsReportService->secondaryScenarioPerformance($from, $to);
        $ctas = $this->analyticsReportService->ctaPerformance($from, $to);
        $popups = $this->analyticsReportService->popupPerformance($from, $to);
        $attribution = $this->analyticsReportService->attributionSummary($from, $to);

        $topDropOff = $funnels
            ->map(function (array $funnel): ?array {
                $dropOff = $funnel['top_drop_off'] ?? null;

                if (! $dropOff) {
                    return null;
                }

                return [
                    'key' => 'top_drop_off',
                    'title' => 'Top Funnel Drop-Off',
                    'detail' => "{$dropOff['label']} is currently the biggest observed drop-off point in {$funnel['label']}.",
                    'evidence' => [
                        'funnel' => $funnel['label'],
                        'drop_off_sessions' => $dropOff['drop_off_to_next'],
                        'step_count' => $dropOff['count'],
                    ],
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $item) => $item['evidence']['drop_off_sessions'])
            ->first();

        $slowestFunnel = $funnels
            ->filter(fn (array $funnel) => $funnel['average_elapsed_seconds'] !== null)
            ->sortByDesc('average_elapsed_seconds')
            ->first();

        $topConversionFunnel = $funnels
            ->sortByDesc('conversion_count')
            ->first();

        $topNonConvertingScenario = $scenarios
            ->filter(fn (array $row) => $row['converted_sessions'] === 0)
            ->sortByDesc('sessions')
            ->first();

        $topAttributedSource = collect($attribution['last_touch'] ?? [])
            ->sortByDesc('conversion_count')
            ->first();

        $assistedPattern = $secondaryScenarios
            ->filter(fn (array $row) => str_contains($row['scenario_key'], 'assisted'))
            ->sortByDesc('sessions')
            ->first();

        $weakCta = $ctas
            ->filter(fn (array $row) => $row['clicks'] > 0)
            ->sortByDesc('clicks')
            ->sortBy('conversion_touch_conversions')
            ->first();

        $weakPopup = $popups
            ->filter(fn (array $row) => $row['opens'] > 0)
            ->sortByDesc('opens')
            ->sortBy('conversion_touch_conversions')
            ->first();

        return collect([
            $topDropOff,
            $slowestFunnel ? [
                'key' => 'slowest_supported_funnel',
                'title' => 'Slowest Supported Funnel',
                'detail' => "{$slowestFunnel['label']} is taking the longest event-based time to complete.",
                'evidence' => [
                    'funnel' => $slowestFunnel['label'],
                    'average_elapsed_seconds' => $slowestFunnel['average_elapsed_seconds'],
                ],
            ] : null,
            $topConversionFunnel ? [
                'key' => 'top_conversion_path',
                'title' => 'Most Common Supported Conversion Path',
                'detail' => "{$topConversionFunnel['label']} has the most observed conversions in the selected range.",
                'evidence' => [
                    'funnel' => $topConversionFunnel['label'],
                    'conversions' => $topConversionFunnel['conversion_count'],
                ],
            ] : null,
            $topAttributedSource ? [
                'key' => 'top_last_touch_source',
                'title' => 'Top Last-Touch Source',
                'detail' => "{$topAttributedSource['source_label']} currently leads last-touch attributed conversions.",
                'evidence' => [
                    'source' => $topAttributedSource['source_label'],
                    'conversions' => $topAttributedSource['conversion_count'],
                    'method' => $topAttributedSource['attribution_method'],
                ],
            ] : null,
            $topNonConvertingScenario ? [
                'key' => 'top_non_converting_scenario',
                'title' => 'Highest-Volume Non-Converting Scenario',
                'detail' => "{$topNonConvertingScenario['label']} is the largest primary scenario without observed conversions.",
                'evidence' => [
                    'scenario' => $topNonConvertingScenario['label'],
                    'sessions' => $topNonConvertingScenario['sessions'],
                    'median_session_duration_seconds' => $topNonConvertingScenario['median_session_duration_seconds'],
                ],
            ] : null,
            $assistedPattern ? [
                'key' => 'top_assisted_pattern',
                'title' => 'Highest-Volume Assisted Pattern',
                'detail' => "{$assistedPattern['label']} is the largest secondary assisted pattern in the selected range.",
                'evidence' => [
                    'scenario' => $assistedPattern['label'],
                    'sessions' => $assistedPattern['sessions'],
                    'conversion_rate' => $assistedPattern['conversion_rate'],
                ],
            ] : null,
            $weakCta ? [
                'key' => 'weak_cta_follow_through',
                'title' => 'CTA With Weak Conversion-Touch Follow-Through',
                'detail' => "{$weakCta['label']} is generating clicks without strong conversion-touch follow-through.",
                'evidence' => [
                    'clicks' => $weakCta['clicks'],
                    'conversion_touch_conversions' => $weakCta['conversion_touch_conversions'],
                    'median_click_to_conversion_seconds' => $weakCta['median_click_to_conversion_seconds'],
                ],
            ] : null,
            $weakPopup ? [
                'key' => 'weak_popup_follow_through',
                'title' => 'Popup With Weak Conversion-Touch Follow-Through',
                'detail' => "{$weakPopup['label']} is opening often but has weaker conversion-touch completion.",
                'evidence' => [
                    'opens' => $weakPopup['opens'],
                    'conversion_touch_conversions' => $weakPopup['conversion_touch_conversions'],
                    'median_open_to_submit_seconds' => $weakPopup['median_open_to_submit_seconds'],
                ],
            ] : null,
        ])->filter()->values();
    }
}
