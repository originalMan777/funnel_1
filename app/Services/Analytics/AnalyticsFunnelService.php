<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Event;
use App\Models\Analytics\Session;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AnalyticsFunnelService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function analyze(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $sessions = $this->sessionsInRange($from, $to);

        return collect([
            $this->buildFunnel(
                key: 'page_to_cta_to_lead_to_conversion',
                label: 'Page to CTA to Lead to Conversion',
                description: 'Session-based path from page view through CTA click, lead submission, and conversion.',
                sessions: $sessions,
                steps: [
                    ['key' => 'page_view', 'label' => 'Page View', 'callback' => fn (array $snapshot) => $snapshot['page_views'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['page_view'] ?? null],
                    ['key' => 'cta_click', 'label' => 'CTA Click', 'callback' => fn (array $snapshot) => $snapshot['cta_clicks'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['cta_click'] ?? null],
                    ['key' => 'lead_submit', 'label' => 'Lead Form Submit', 'callback' => fn (array $snapshot) => $snapshot['lead_form_submitted'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['lead_submit'] ?? null],
                    ['key' => 'conversion', 'label' => 'Conversion', 'callback' => fn (array $snapshot) => $snapshot['converted'], 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['conversion'] ?? null],
                ],
            ),
            $this->buildFunnel(
                key: 'lead_box_capture',
                label: 'Lead-Box Capture',
                description: 'Lead-box impression to click to lead submission, with conversion count at the end.',
                sessions: $sessions,
                steps: [
                    ['key' => 'lead_box_impression', 'label' => 'Lead Box Impression', 'callback' => fn (array $snapshot) => $snapshot['lead_box_impressions'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['lead_box_impression'] ?? null],
                    ['key' => 'lead_box_click', 'label' => 'Lead Box Click', 'callback' => fn (array $snapshot) => $snapshot['lead_box_clicks'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['lead_box_click'] ?? null],
                    ['key' => 'lead_submit', 'label' => 'Lead Form Submit', 'callback' => fn (array $snapshot) => $snapshot['lead_form_submitted'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['lead_submit'] ?? null],
                    ['key' => 'conversion', 'label' => 'Conversion', 'callback' => fn (array $snapshot) => $snapshot['converted'], 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['conversion'] ?? null],
                ],
            ),
            $this->buildPopupFunnel($sessions),
            $this->buildFunnel(
                key: 'cta_impression_to_conversion',
                label: 'CTA Impression to Conversion',
                description: 'CTA impression to click with directly supportable CTA-linked conversions.',
                sessions: $sessions,
                steps: [
                    ['key' => 'cta_impression', 'label' => 'CTA Impression', 'callback' => fn (array $snapshot) => $snapshot['cta_impressions'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['cta_impression'] ?? null],
                    ['key' => 'cta_click', 'label' => 'CTA Click', 'callback' => fn (array $snapshot) => $snapshot['cta_clicks'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['cta_click'] ?? null],
                    ['key' => 'cta_conversion', 'label' => 'CTA-Linked Conversion', 'callback' => fn (array $snapshot) => $snapshot['cta_linked_conversions'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['cta_conversion'] ?? null],
                ],
            ),
        ]);
    }

    /**
     * @return Collection<int, Session>
     */
    private function sessionsInRange(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Session::query()
            ->whereBetween('started_at', [
                $from->copy()->startOfDay()->toDateTimeString(),
                $to->copy()->endOfDay()->toDateTimeString(),
            ])
            ->with([
                'events.eventType',
                'conversions',
            ])
            ->orderBy('started_at')
            ->get();
    }

    /**
     * @param  list<array{key:string,label:string,callback:\Closure(array): bool,timestamp:\Closure(array): mixed}>  $steps
     * @return array<string, mixed>
     */
    private function buildFunnel(
        string $key,
        string $label,
        string $description,
        Collection $sessions,
        array $steps,
    ): array {
        $snapshots = $sessions->map(fn (Session $session) => $this->sessionSnapshot($session))->values();
        $stepRows = [];
        $qualified = $snapshots;
        $timingSamples = [];

        foreach ($steps as $index => $step) {
            $qualified = $qualified->filter(fn (array $snapshot) => $step['callback']($snapshot))->values();
            $count = $qualified->count();
            $stepRows[] = [
                'key' => $step['key'],
                'label' => $step['label'],
                'count' => $count,
                'drop_off_to_next' => 0,
            ];

            if ($index > 0) {
                $previousStep = $steps[$index - 1];
                $stepRows[$index - 1]['drop_off_to_next'] = max($stepRows[$index - 1]['count'] - $count, 0);

                $samples = $qualified
                    ->map(function (array $snapshot) use ($previousStep, $step): ?float {
                        return $this->elapsedSeconds(
                            $previousStep['timestamp']($snapshot),
                            $step['timestamp']($snapshot),
                        );
                    })
                    ->filter(fn (?float $seconds) => $seconds !== null)
                    ->values();

                $timingSamples[] = [
                    'key' => sprintf('%s_to_%s', $previousStep['key'], $step['key']),
                    'label' => sprintf('%s -> %s', $previousStep['label'], $step['label']),
                    'average_elapsed_seconds' => $this->averageSeconds($samples),
                ];
            }
        }

        $topDropOff = collect($stepRows)
            ->filter(fn (array $row) => $row['drop_off_to_next'] > 0)
            ->sortByDesc('drop_off_to_next')
            ->first();

        $firstStep = $steps[0] ?? null;
        $lastStep = last($steps) ?: null;
        $finalQualified = $lastStep
            ? $snapshots->filter(fn (array $snapshot) => $lastStep['callback']($snapshot))->values()
            : collect();
        $elapsedSamples = $finalQualified
            ->map(function (array $snapshot) use ($firstStep, $lastStep): ?float {
                if (! $firstStep || ! $lastStep) {
                    return null;
                }

                return $this->elapsedSeconds(
                    $firstStep['timestamp']($snapshot),
                    $lastStep['timestamp']($snapshot),
                );
            })
            ->filter(fn (?float $seconds) => $seconds !== null)
            ->values();

        return [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'steps' => $stepRows,
            'conversion_count' => (int) data_get(last($stepRows) ?: [], 'count', 0),
            'top_drop_off' => $topDropOff,
            'average_elapsed_seconds' => $this->averageSeconds($elapsedSamples),
            'step_timings' => $timingSamples,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPopupFunnel(Collection $sessions): array
    {
        $snapshots = $sessions->map(fn (Session $session) => $this->sessionSnapshot($session))->values();

        $funnel = $this->buildFunnel(
            key: 'popup_lifecycle',
            label: 'Popup Lifecycle',
            description: 'Popup eligibility to impression to open to submission.',
            sessions: $sessions,
            steps: [
                ['key' => 'popup_eligible', 'label' => 'Popup Eligible', 'callback' => fn (array $snapshot) => $snapshot['popup_eligible'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['popup_eligible'] ?? null],
                ['key' => 'popup_impression', 'label' => 'Popup Impression', 'callback' => fn (array $snapshot) => $snapshot['popup_impressions'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['popup_impression'] ?? null],
                ['key' => 'popup_open', 'label' => 'Popup Open', 'callback' => fn (array $snapshot) => $snapshot['popup_opens'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['popup_open'] ?? null],
                ['key' => 'popup_submit', 'label' => 'Popup Submit', 'callback' => fn (array $snapshot) => $snapshot['popup_submitted'] > 0, 'timestamp' => fn (array $snapshot) => $snapshot['step_timestamps']['popup_submit'] ?? null],
            ],
        );

        $dismissedWithoutSubmit = $snapshots
            ->filter(fn (array $snapshot) => $snapshot['popup_dismissals'] > 0 && $snapshot['popup_submitted'] === 0)
            ->count();
        $dismissedSamples = $snapshots
            ->map(fn (array $snapshot) => $this->elapsedSeconds(
                $snapshot['step_timestamps']['popup_open'] ?? null,
                $snapshot['step_timestamps']['popup_dismiss'] ?? null,
            ))
            ->filter(fn (?float $seconds) => $seconds !== null)
            ->values();
        $submittedSamples = $snapshots
            ->map(fn (array $snapshot) => $this->elapsedSeconds(
                $snapshot['step_timestamps']['popup_open'] ?? null,
                $snapshot['step_timestamps']['popup_submit'] ?? null,
            ))
            ->filter(fn (?float $seconds) => $seconds !== null)
            ->values();

        $funnel['dismissed_without_submit'] = $dismissedWithoutSubmit;
        $funnel['special_timings'] = [
            [
                'key' => 'popup_open_to_submit',
                'label' => 'Popup Open -> Popup Submit',
                'average_elapsed_seconds' => $this->averageSeconds($submittedSamples),
            ],
            [
                'key' => 'popup_open_to_dismiss',
                'label' => 'Popup Open -> Popup Dismiss',
                'average_elapsed_seconds' => $this->averageSeconds($dismissedSamples),
            ],
        ];

        return $funnel;
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionSnapshot(Session $session): array
    {
        $events = $session->events
            ->sortBy(fn (Event $event) => sprintf('%s-%010d', optional($event->occurred_at)?->format('YmdHis'), $event->id))
            ->values();
        $eventCounts = $events
            ->map(fn (Event $event) => $event->eventType?->event_key)
            ->filter()
            ->countBy();
        $firstEventTimestamps = $events
            ->filter(fn (Event $event) => $event->eventType?->event_key !== null)
            ->groupBy(fn (Event $event) => $event->eventType?->event_key)
            ->map(fn (Collection $group) => $group->first()?->occurred_at);
        $firstConversion = $session->conversions
            ->sortBy(fn ($conversion) => sprintf('%s-%010d', optional($conversion->occurred_at)?->format('YmdHis'), $conversion->id))
            ->first();
        $firstCtaLinkedConversion = $session->conversions
            ->filter(fn ($conversion) => $conversion->cta_id !== null)
            ->sortBy(fn ($conversion) => sprintf('%s-%010d', optional($conversion->occurred_at)?->format('YmdHis'), $conversion->id))
            ->first();

        return [
            'page_views' => (int) ($eventCounts['page.view'] ?? 0),
            'cta_impressions' => (int) ($eventCounts['cta.impression'] ?? 0),
            'cta_clicks' => (int) ($eventCounts['cta.click'] ?? 0),
            'lead_box_impressions' => (int) ($eventCounts['lead_box.impression'] ?? 0),
            'lead_box_clicks' => (int) ($eventCounts['lead_box.click'] ?? 0),
            'lead_form_submitted' => (int) ($eventCounts['lead_form.submitted'] ?? 0),
            'popup_eligible' => (int) ($eventCounts['popup.eligible'] ?? 0),
            'popup_impressions' => (int) ($eventCounts['popup.impression'] ?? 0),
            'popup_opens' => (int) ($eventCounts['popup.opened'] ?? 0),
            'popup_dismissals' => (int) ($eventCounts['popup.dismissed'] ?? 0),
            'popup_submitted' => (int) ($eventCounts['popup.submitted'] ?? 0),
            'cta_linked_conversions' => $session->conversions->filter(fn ($conversion) => $conversion->cta_id !== null)->count(),
            'converted' => $session->conversions->isNotEmpty(),
            'step_timestamps' => [
                'page_view' => $firstEventTimestamps->get('page.view'),
                'cta_impression' => $firstEventTimestamps->get('cta.impression'),
                'cta_click' => $firstEventTimestamps->get('cta.click'),
                'lead_box_impression' => $firstEventTimestamps->get('lead_box.impression'),
                'lead_box_click' => $firstEventTimestamps->get('lead_box.click'),
                'lead_submit' => $firstEventTimestamps->get('lead_form.submitted'),
                'popup_eligible' => $firstEventTimestamps->get('popup.eligible'),
                'popup_impression' => $firstEventTimestamps->get('popup.impression'),
                'popup_open' => $firstEventTimestamps->get('popup.opened'),
                'popup_dismiss' => $firstEventTimestamps->get('popup.dismissed'),
                'popup_submit' => $firstEventTimestamps->get('popup.submitted'),
                'conversion' => $firstConversion?->occurred_at,
                'cta_conversion' => $firstCtaLinkedConversion?->occurred_at,
            ],
        ];
    }

    private function elapsedSeconds(mixed $start, mixed $end): ?float
    {
        if (! $start || ! $end) {
            return null;
        }

        $seconds = $end->getTimestamp() - $start->getTimestamp();

        if ($seconds < 0) {
            return null;
        }

        return (float) $seconds;
    }

    private function averageSeconds(Collection $samples): ?float
    {
        if ($samples->isEmpty()) {
            return null;
        }

        return round((float) $samples->avg(), 1);
    }
}
