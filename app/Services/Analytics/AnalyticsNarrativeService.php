<?php

namespace App\Services\Analytics;

class AnalyticsNarrativeService
{
    /**
     * @param  array<string, mixed>  $summaryCards
     * @return array{paragraphs:array<int, string>}
     */
    public function overviewReport(array $summaryCards): array
    {
        $views = $this->number($summaryCards['page_views'] ?? 0);
        $clicks = $this->number($summaryCards['cta_clicks'] ?? 0);
        $conversions = $this->number($summaryCards['conversions'] ?? 0);
        $submissions = $this->number(
            (float) ($summaryCards['lead_form_submissions'] ?? 0)
            + (float) ($summaryCards['popup_submissions'] ?? 0)
        );
        $timeToConversion = $this->duration($summaryCards['average_time_to_conversion_seconds'] ?? null);

        $conversionRate = $this->percent(
            (float) ($summaryCards['conversions'] ?? 0),
            max((float) ($summaryCards['page_views'] ?? 0), 1)
        );

        return [
            'paragraphs' => [
                "System health is showing {$views} page views, {$clicks} CTA clicks, {$submissions} lead submissions, and {$conversions} conversions for the selected range. Conversion performance is running at {$conversionRate} against page volume, with average time to conversion at {$timeToConversion}.",
                "The strongest signal is that traffic and outcome activity are both present, giving the system enough movement to compare quality across pages, CTAs, capture surfaces, and sources. The next priority is to reduce delay and isolate weak conversion paths where high visibility does not translate into proportional submissions or attributed conversions.",
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $cluster
     * @param  array<int, array<string, mixed>>  $subClusters
     * @return array{summary:string}
     */
    public function clusterReport(array $cluster, array $subClusters): array
    {
        $groupCount = collect($subClusters)->sum(fn (array $subCluster) => count($subCluster['metricGroups'] ?? []));
        $metricCount = collect($subClusters)
            ->flatMap(fn (array $subCluster) => $subCluster['metricGroups'] ?? [])
            ->sum(fn (array $group) => count($group['metrics'] ?? []));
        $headline = $this->firstMetricValue($subClusters);

        $summary = match ($cluster['key'] ?? null) {
            'traffic' => "Traffic is reporting {$groupCount} configured performance groups and {$metricCount} tracked signals, led by {$headline}. Volume is visible, while conversion and timing metrics point to optimization opportunities across pages and CTAs.",
            'capture' => "Lead capture is active across {$groupCount} lifecycle groups and {$metricCount} tracked signals, led by {$headline}. Submission movement is present, while failures, dismissals, and duration should guide refinement.",
            'flow' => "Flow reporting is focused on {$groupCount} funnel group with {$metricCount} progression signals. Completion, drop-off, and duration should be read together to identify where momentum weakens.",
            'behavior' => "Behavior reporting is focused on {$groupCount} scenario group with {$metricCount} session signals. Scenario volume and conversion rate should guide which behavior patterns deserve deeper review.",
            'results' => "Conversion reporting is focused on {$groupCount} outcome group with {$metricCount} result signals. Submission volume and time to conversion indicate whether outcomes are both occurring and arriving efficiently.",
            'source' => "Source reporting is focused on {$groupCount} attribution group with {$metricCount} attribution signals. Coverage and attributed conversions show whether outcomes can be tied back to source quality.",
            default => "This cluster is reporting {$groupCount} configured groups and {$metricCount} tracked signals, led by {$headline}. Review the strongest values alongside weak statuses before prioritizing changes.",
        };

        return ['summary' => $summary];
    }

    /**
     * @param  array<string, mixed>  $metricGroup
     * @return array{summary:string}
     */
    public function groupReport(array $metricGroup): array
    {
        $metrics = collect($metricGroup['metrics'] ?? []);
        $values = $metrics
            ->map(fn (array $metric) => sprintf('%s: %s', $metric['label'] ?? $metric['key'], $metric['displayValue'] ?? $metric['value'] ?? '—'))
            ->take(3)
            ->implode(', ');

        $summary = match ($metricGroup['key'] ?? null) {
            'page_performance' => "Pages are generating visibility and conversion signals across {$values}. Differences between reach, conversion rate, and timing should guide which content receives optimization first.",
            'cta_performance' => "CTA engagement is active across {$values}. Click-through and conversion follow-through should be compared together before changing messaging or placement.",
            'lead_box_lifecycle' => "Lead boxes are producing lifecycle movement across {$values}. Submission strength should be weighed against failures and completion duration.",
            'popup_lifecycle' => "Popups are showing lifecycle activity across {$values}. Opens, dismissals, and submissions should be balanced before increasing exposure.",
            'funnel_performance' => "Funnels are reporting progression signals across {$values}. Completion rate, drop-off, and duration identify where journey friction is most likely.",
            'scenario_performance' => "Scenarios are reporting behavior quality across {$values}. High-volume scenarios should be compared against conversion efficiency before prioritizing changes.",
            'conversion_performance' => "Conversions are reporting outcome health across {$values}. Submission volume and timing indicate whether the path is producing results efficiently.",
            'attribution_performance' => "Attribution is reporting source clarity across {$values}. Coverage and attributed submissions should guide source ranking and tracking cleanup.",
            default => "This metric group is reporting {$values}. Review the values with their statuses and recommendations before changing the underlying experience.",
        };

        return ['summary' => $summary];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subClusters
     */
    private function firstMetricValue(array $subClusters): string
    {
        $metric = collect($subClusters)
            ->flatMap(fn (array $subCluster) => $subCluster['metricGroups'] ?? [])
            ->flatMap(fn (array $group) => $group['metrics'] ?? [])
            ->first();

        if (! $metric) {
            return 'no configured value';
        }

        return sprintf('%s at %s', $metric['label'] ?? $metric['key'], $metric['displayValue'] ?? $metric['value'] ?? '—');
    }

    private function number(float|int|string $value): string
    {
        return number_format((float) $value, (float) $value === floor((float) $value) ? 0 : 2);
    }

    private function percent(float $numerator, float $denominator): string
    {
        return number_format($denominator > 0 ? ($numerator / $denominator) * 100 : 0, 2).'%';
    }

    private function duration(mixed $seconds): string
    {
        if ($seconds === null || $seconds === '') {
            return 'no measured timing yet';
        }

        $seconds = (float) $seconds;

        if ($seconds < 60) {
            return number_format($seconds, 0).' seconds';
        }

        return number_format($seconds / 60, 1).' minutes';
    }
}
