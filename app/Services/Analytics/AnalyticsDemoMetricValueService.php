<?php

namespace App\Services\Analytics;

use Illuminate\Support\Arr;

class AnalyticsDemoMetricValueService
{
    /**
     * @return array<string, mixed>
     */
    public function fallbackFor(
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
        string $metricKey,
    ): array {
        $definition = $this->definition($clusterKey, $subClusterKey, $metricGroupKey, $metricKey);

        return [
            ...Arr::except($definition, ['numericValue']),
            'value' => $definition['value'],
            'displayValue' => $definition['displayValue'] ?? $definition['value'],
            'dataSource' => 'local_demo',
            'parsedData' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $metric
     * @return array<string, mixed>
     */
    public function interpretationFor(
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
        string $metricKey,
        array $metric,
    ): array {
        $definition = $this->definition($clusterKey, $subClusterKey, $metricGroupKey, $metricKey);
        $numericValue = $this->parseMetricValue($metric['value'] ?? null);
        $status = $numericValue !== null
            ? $this->statusFor($metricKey, $numericValue)
            : ($definition['status'] ?? 'neutral');

        return [
            'status' => $status,
            'statusLabel' => $this->statusLabel($status),
            'trendLabel' => $metric['trendLabel'] ?? $definition['trendLabel'] ?? null,
            'delta' => $metric['delta'] ?? $definition['delta'] ?? null,
            'insight' => $definition['insight'],
            'recommendation' => $definition['recommendation'],
        ];
    }

    /**
     * @return array{definition:string,formula:string,whyItMatters:string}
     */
    public function definitionFor(
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
        string $metricKey,
    ): array {
        $contextDefinitions = [
            $this->contextKey('traffic', 'ctas', 'cta_performance', 'views') => [
                'definition' => 'Total number of recorded CTA impressions.',
                'formula' => 'Count of tracked CTA impression events.',
                'whyItMatters' => 'Shows how often calls to action are being seen before users decide whether to engage.',
            ],
            $this->contextKey('capture', 'lead_boxes', 'lead_box_lifecycle', 'views') => [
                'definition' => 'Total number of recorded lead box impressions.',
                'formula' => 'Count of tracked lead box impression events.',
                'whyItMatters' => 'Indicates whether lead capture surfaces are receiving enough exposure to produce submissions.',
            ],
            $this->contextKey('capture', 'popups', 'popup_lifecycle', 'views') => [
                'definition' => 'Total number of recorded popup impressions.',
                'formula' => 'Count of tracked popup impression events.',
                'whyItMatters' => 'Shows how often popup experiences are appearing and creating an opportunity to engage.',
            ],
            $this->contextKey('behavior', 'scenarios', 'scenario_performance', 'views') => [
                'definition' => 'Total number of sessions assigned to the scenario performance group.',
                'formula' => 'Count of sessions with a primary scenario assignment.',
                'whyItMatters' => 'Reveals which behavior patterns have enough volume to evaluate reliably.',
            ],
            $this->contextKey('source', 'attribution', 'attribution_performance', 'submissions') => [
                'definition' => 'Total number of conversions with a recorded attribution source.',
                'formula' => 'Count of attributed conversions.',
                'whyItMatters' => 'Shows how much conversion activity can be connected back to source performance.',
            ],
        ];

        return $contextDefinitions[$this->contextKey($clusterKey, $subClusterKey, $metricGroupKey, $metricKey)]
            ?? $this->baseDefinitions()[$metricKey]
            ?? [
                'definition' => 'Configured analytics metric for this reporting context.',
                'formula' => 'Derived from the configured analytics event and rollup pipeline.',
                'whyItMatters' => 'Helps operators evaluate performance in this analytics group.',
            ];
    }

    private function contextKey(
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
        string $metricKey,
    ): string {
        return implode('|', [$clusterKey, $subClusterKey, $metricGroupKey, $metricKey]);
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(
        string $clusterKey,
        string $subClusterKey,
        string $metricGroupKey,
        string $metricKey,
    ): array {
        return $this->definitions()[$this->contextKey($clusterKey, $subClusterKey, $metricGroupKey, $metricKey)]
            ?? [
                'value' => '—',
                'displayValue' => '—',
                'status' => 'neutral',
                'trendLabel' => 'Demo baseline pending',
                'insight' => 'This metric is configured, but no demo interpretation has been mapped yet.',
                'recommendation' => 'Review the metric definition before using it for operator decisions.',
            ];
    }

    private function parseMetricValue(mixed $value): ?float
    {
        if ($value === null || $value === '' || $value === '—') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : null;
        }

        $normalized = str_replace([',', '%'], '', (string) $value);
        $parsed = (float) trim($normalized);

        return is_numeric(trim($normalized)) ? $parsed : null;
    }

    private function statusFor(string $metricKey, float $value): string
    {
        return match ($metricKey) {
            'failures', 'dismissals', 'drop_off', 'duration', 'time_to_conversion' => $value <= 20 ? 'good' : ($value <= 55 ? 'warning' : 'poor'),
            'ctr', 'conversion_rate', 'open_rate', 'completion_rate', 'attribution_coverage' => $value >= 45 ? 'good' : ($value >= 18 ? 'warning' : 'poor'),
            default => $value > 0 ? 'good' : 'neutral',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'good' => 'Healthy',
            'warning' => 'Watch',
            'poor' => 'Needs Review',
            default => 'Baseline',
        };
    }

    /**
     * @return array<string, array{definition:string,formula:string,whyItMatters:string}>
     */
    private function baseDefinitions(): array
    {
        return [
            'views' => [
                'definition' => 'Total number of recorded page or component views.',
                'formula' => 'Count of tracked view events.',
                'whyItMatters' => 'Indicates traffic volume and reach.',
            ],
            'clicks' => [
                'definition' => 'Total number of recorded user clicks on the tracked component.',
                'formula' => 'Count of tracked click events.',
                'whyItMatters' => 'Measures active engagement after a user sees an offer or action.',
            ],
            'ctr' => [
                'definition' => 'Percentage of CTA impressions that resulted in clicks.',
                'formula' => 'Clicks / Impressions.',
                'whyItMatters' => 'Shows whether CTA placement and messaging are strong enough to earn action.',
            ],
            'conversion_rate' => [
                'definition' => 'Percentage of users or interactions that completed a desired action.',
                'formula' => 'Conversions / Eligible views or interactions.',
                'whyItMatters' => 'Measures how effectively traffic turns into outcomes.',
            ],
            'submissions' => [
                'definition' => 'Total number of recorded successful submissions or conversions.',
                'formula' => 'Count of tracked submission or conversion events.',
                'whyItMatters' => 'Represents the outcome volume produced by the current path.',
            ],
            'dismissals' => [
                'definition' => 'Total number of recorded dismiss actions.',
                'formula' => 'Count of tracked dismissal events.',
                'whyItMatters' => 'Highlights interruption, mismatch, or timing friction in an engagement surface.',
            ],
            'failures' => [
                'definition' => 'Total number of recorded failed submission attempts.',
                'formula' => 'Count of tracked failure events.',
                'whyItMatters' => 'Surfaces technical or validation friction that can silently suppress leads.',
            ],
            'open_rate' => [
                'definition' => 'Percentage of popup impressions that progressed into opens.',
                'formula' => 'Popup opens / Popup impressions.',
                'whyItMatters' => 'Measures whether the popup moment earns enough attention to justify downstream optimization.',
            ],
            'completion_rate' => [
                'definition' => 'Percentage of funnel entrants that reached the supported end state.',
                'formula' => 'Completed funnel sessions / Funnel entrants.',
                'whyItMatters' => 'Shows how effectively the journey carries users from entry to outcome.',
            ],
            'drop_off' => [
                'definition' => 'Number of users or sessions lost between supported funnel steps.',
                'formula' => 'Previous step count - Next step count.',
                'whyItMatters' => 'Identifies where momentum breaks inside the path.',
            ],
            'duration' => [
                'definition' => 'Elapsed time spent completing the tracked interaction or journey.',
                'formula' => 'Average(end_time - start_time).',
                'whyItMatters' => 'Highlights friction, delay, and unnecessary complexity in the experience.',
            ],
            'time_to_conversion' => [
                'definition' => 'Elapsed time from initial interaction to conversion.',
                'formula' => 'Average(conversion_time - first_interaction_time).',
                'whyItMatters' => 'Highlights friction and delay in the conversion process.',
            ],
            'attribution_coverage' => [
                'definition' => 'Percentage of conversions with an associated attribution source.',
                'formula' => 'Attributed conversions / Total conversions.',
                'whyItMatters' => 'Shows how much outcome activity can be explained by source and campaign signals.',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            'traffic|ctas|cta_performance|ctr' => ['value' => '3.8%', 'status' => 'warning', 'trendLabel' => '+0.4 pts vs demo baseline', 'delta' => '+0.4 pts', 'insight' => 'CTA engagement is present, but click efficiency has room to improve.', 'recommendation' => 'Compare high-impression CTAs against high-click CTAs to tighten copy and placement.'],
            'traffic|pages|page_performance|conversion_rate' => ['value' => '6.4%', 'status' => 'warning', 'trendLabel' => '+0.8 pts vs demo baseline', 'delta' => '+0.8 pts', 'insight' => 'Pages are attracting traffic, but conversion efficiency needs review.', 'recommendation' => 'Compare high-view pages against high-conversion pages to find where traffic quality drops.'],
            'traffic|ctas|cta_performance|conversion_rate' => ['value' => '12.6%', 'status' => 'warning', 'trendLabel' => '-1.1 pts vs demo baseline', 'delta' => '-1.1 pts', 'insight' => 'CTA clicks are converting at a moderate rate.', 'recommendation' => 'Inspect destination fit for CTAs with clicks but weaker conversion follow-through.'],
            'capture|popups|popup_lifecycle|open_rate' => ['value' => '42.3%', 'status' => 'warning', 'trendLabel' => '+2.6 pts vs demo baseline', 'delta' => '+2.6 pts', 'insight' => 'Popup opens are healthy enough to evaluate downstream behavior.', 'recommendation' => 'Segment opens by trigger and page to identify which moments earn attention.'],
            'flow|funnels|funnel_performance|completion_rate' => ['value' => '31.8%', 'status' => 'warning', 'trendLabel' => '+3.2 pts vs demo baseline', 'delta' => '+3.2 pts', 'insight' => 'The funnel is moving users forward, but completion is not yet strong.', 'recommendation' => 'Start with the largest drop-off step before changing entry traffic.'],
            'flow|funnels|funnel_performance|drop_off' => ['value' => '27', 'status' => 'warning', 'trendLabel' => '-5 vs demo baseline', 'delta' => '-5', 'insight' => 'A meaningful group of sessions is leaving before the next funnel step.', 'recommendation' => 'Inspect the step before the top drop-off for friction or unclear intent.'],
            'traffic|pages|page_performance|time_to_conversion' => ['value' => '128', 'displayValue' => '128', 'status' => 'warning', 'trendLabel' => '-18s vs demo baseline', 'delta' => '-18s', 'insight' => 'Page-assisted conversions are taking a little over two minutes.', 'recommendation' => 'Review whether high-intent pages surface the next action quickly enough.'],
            'traffic|ctas|cta_performance|time_to_conversion' => ['value' => '91', 'displayValue' => '91', 'status' => 'warning', 'trendLabel' => '-12s vs demo baseline', 'delta' => '-12s', 'insight' => 'CTA-assisted conversions are happening faster than page-only paths.', 'recommendation' => 'Preserve CTA context through the destination experience to keep momentum.'],
            'results|conversions|conversion_performance|time_to_conversion' => ['value' => '164', 'displayValue' => '164', 'status' => 'warning', 'trendLabel' => '-21s vs demo baseline', 'delta' => '-21s', 'insight' => 'Overall conversion timing is acceptable but still leaves room for path cleanup.', 'recommendation' => 'Prioritize journeys with multiple hesitation points before conversion.'],
            'source|attribution|attribution_performance|attribution_coverage' => ['value' => '68.5%', 'status' => 'good', 'trendLabel' => '+4.5 pts vs demo baseline', 'delta' => '+4.5 pts', 'insight' => 'Most conversions have an attributed source in the demo report.', 'recommendation' => 'Investigate unattributed conversions to improve campaign and referral clarity.'],
            'traffic|pages|page_performance|views' => ['value' => '4,820', 'status' => 'good', 'trendLabel' => '+14% vs demo baseline', 'delta' => '+14%', 'insight' => 'Page reach is strong enough to compare quality across destinations.', 'recommendation' => 'Pair view volume with conversion rate before promoting top pages.'],
            'traffic|ctas|cta_performance|views' => ['value' => '12,940', 'status' => 'good', 'trendLabel' => '+9% vs demo baseline', 'delta' => '+9%', 'insight' => 'CTAs are receiving broad exposure across tracked surfaces.', 'recommendation' => 'Use CTR to decide which high-exposure placements deserve iteration.'],
            'traffic|ctas|cta_performance|clicks' => ['value' => '492', 'status' => 'good', 'trendLabel' => '+11% vs demo baseline', 'delta' => '+11%', 'insight' => 'CTA click volume is sufficient for directional comparison.', 'recommendation' => 'Rank clicks alongside conversion rate to avoid optimizing for curiosity alone.'],
            'capture|lead_boxes|lead_box_lifecycle|views' => ['value' => '2,380', 'status' => 'good', 'trendLabel' => '+7% vs demo baseline', 'delta' => '+7%', 'insight' => 'Lead boxes are getting enough impressions to evaluate lifecycle health.', 'recommendation' => 'Compare impressions to clicks to find weak offer placement.'],
            'capture|lead_boxes|lead_box_lifecycle|clicks' => ['value' => '318', 'status' => 'good', 'trendLabel' => '+6% vs demo baseline', 'delta' => '+6%', 'insight' => 'Lead box clicks show active interest in the offer.', 'recommendation' => 'Review forms with clicks but lower submissions for friction.'],
            'capture|lead_boxes|lead_box_lifecycle|submissions' => ['value' => '126', 'status' => 'good', 'trendLabel' => '+10% vs demo baseline', 'delta' => '+10%', 'insight' => 'Lead boxes are producing steady demo submissions.', 'recommendation' => 'Protect the strongest lead box path before experimenting with new variants.'],
            'capture|popups|popup_lifecycle|views' => ['value' => '1,740', 'status' => 'good', 'trendLabel' => '+5% vs demo baseline', 'delta' => '+5%', 'insight' => 'Popup impressions are high enough to assess lifecycle behavior.', 'recommendation' => 'Watch open rate and dismissals together before increasing frequency.'],
            'capture|popups|popup_lifecycle|submissions' => ['value' => '88', 'status' => 'good', 'trendLabel' => '+8% vs demo baseline', 'delta' => '+8%', 'insight' => 'Popup submissions are contributing measurable lead capture volume.', 'recommendation' => 'Identify which popup triggers produce submissions without raising dismissals.'],
            'capture|popups|popup_lifecycle|dismissals' => ['value' => '246', 'status' => 'warning', 'trendLabel' => '-3% vs demo baseline', 'delta' => '-3%', 'insight' => 'Dismissals are material but not overwhelming in the demo report.', 'recommendation' => 'Reduce interruption on pages where dismissals outpace submissions.'],
            'results|conversions|conversion_performance|submissions' => ['value' => '214', 'status' => 'good', 'trendLabel' => '+12% vs demo baseline', 'delta' => '+12%', 'insight' => 'Conversion volume is strong enough to support trend review.', 'recommendation' => 'Break conversions down by source and entry page before scaling spend.'],
            'capture|lead_boxes|lead_box_lifecycle|failures' => ['value' => '9', 'status' => 'good', 'trendLabel' => '-2 vs demo baseline', 'delta' => '-2', 'insight' => 'Lead form failures are low in the demo report.', 'recommendation' => 'Keep monitoring failures after form or validation changes.'],
            'capture|lead_boxes|lead_box_lifecycle|duration' => ['value' => '46', 'displayValue' => '46', 'status' => 'warning', 'trendLabel' => '-6s vs demo baseline', 'delta' => '-6s', 'insight' => 'Lead box completion timing is moderate.', 'recommendation' => 'Shorten the path between offer click and form completion where possible.'],
            'capture|popups|popup_lifecycle|duration' => ['value' => '34', 'displayValue' => '34', 'status' => 'warning', 'trendLabel' => '-4s vs demo baseline', 'delta' => '-4s', 'insight' => 'Popup interactions are resolving within a short window.', 'recommendation' => 'Preserve quick submission paths and trim copy that delays action.'],
            'flow|funnels|funnel_performance|duration' => ['value' => '236', 'displayValue' => '236', 'status' => 'poor', 'trendLabel' => '+19s vs demo baseline', 'delta' => '+19s', 'insight' => 'Funnel completion is taking longer than desired.', 'recommendation' => 'Find the slowest transition and simplify the next required action.'],
            'behavior|scenarios|scenario_performance|views' => ['value' => '1,284', 'status' => 'good', 'trendLabel' => '+16% vs demo baseline', 'delta' => '+16%', 'insight' => 'Scenario volume is broad enough to compare behavior patterns.', 'recommendation' => 'Rank scenarios by conversion rate, not only by session count.'],
            'behavior|scenarios|scenario_performance|conversion_rate' => ['value' => '18.7%', 'status' => 'warning', 'trendLabel' => '+1.9 pts vs demo baseline', 'delta' => '+1.9 pts', 'insight' => 'Scenario conversion rate is promising but uneven.', 'recommendation' => 'Compare top scenarios against poor performers to isolate intent signals.'],
            'behavior|scenarios|scenario_performance|duration' => ['value' => '142', 'displayValue' => '142', 'status' => 'warning', 'trendLabel' => '-9s vs demo baseline', 'delta' => '-9s', 'insight' => 'Scenario sessions show a moderate engagement window.', 'recommendation' => 'Review long sessions that do not convert for decision friction.'],
            'source|attribution|attribution_performance|submissions' => ['value' => '147', 'status' => 'good', 'trendLabel' => '+13% vs demo baseline', 'delta' => '+13%', 'insight' => 'Attributed conversions provide enough signal for source ranking.', 'recommendation' => 'Compare source quality before increasing volume from top channels.'],
        ];
    }
}
