<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Session;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnalyticsSessionJourneyService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recentSessions(
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $scenarioKey = null,
        int $limit = 8,
    ): Collection {
        $query = Session::query()
            ->whereBetween('started_at', [
                $from->copy()->startOfDay()->toDateTimeString(),
                $to->copy()->endOfDay()->toDateTimeString(),
            ])
            ->with([
                'entryPage',
                'events.eventType',
                'events.page',
                'events.cta',
                'events.leadBox',
                'events.popup',
                'conversions',
                'scenarioAssignment.scenarioDefinition',
                'secondaryScenarioAssignments.scenarioDefinition',
            ])
            ->orderByDesc('started_at')
            ->limit($limit);

        if ($scenarioKey) {
            $query->whereHas('scenarioAssignment.scenarioDefinition', function ($scenarioQuery) use ($scenarioKey): void {
                $scenarioQuery->where('scenario_key', $scenarioKey);
            });
        }

        return $query->get()
            ->map(fn (Session $session) => $this->summarizeSession($session));
    }

    /**
     * @return array<string, mixed>
     */
    public function summarizeSession(Session $session, int $maxEvents = 8): array
    {
        $session->loadMissing([
            'entryPage',
            'events.eventType',
            'events.page',
            'events.cta',
            'events.leadBox',
            'events.popup',
            'conversions',
            'scenarioAssignment.scenarioDefinition',
            'secondaryScenarioAssignments.scenarioDefinition',
        ]);

        $events = $session->events
            ->sortBy(fn ($event) => sprintf('%s-%010d', optional($event->occurred_at)?->format('YmdHis.u'), $event->id))
            ->values();
        $distinctPages = collect([$session->entry_page_id])
            ->merge($events->pluck('page_id'))
            ->filter()
            ->unique()
            ->count();
        $eventSummaries = $events
            ->take($maxEvents)
            ->map(function ($event) use ($events) {
                $firstEventAt = $events->first()?->occurred_at;

                return [
                    'event_key' => $event->eventType?->event_key,
                    'label' => $event->eventType?->label ?? Str::headline(str_replace('.', ' ', (string) $event->eventType?->event_key)),
                    'context' => $event->cta?->label
                        ?? $event->leadBox?->title
                        ?? $event->popup?->name
                        ?? $event->page?->label,
                    'occurred_at' => $event->occurred_at?->toDateTimeString(),
                    'elapsed_from_first_event_seconds' => $this->elapsedSeconds($firstEventAt, $event->occurred_at),
                ];
            })
            ->values();

        return [
            'id' => $session->id,
            'session_key' => $session->session_key,
            'started_at' => $session->started_at?->toDateTimeString(),
            'ended_at' => $session->ended_at?->toDateTimeString(),
            'entry_page' => $session->entryPage?->label,
            'converted' => $session->conversions->isNotEmpty(),
            'conversion_count' => $session->conversions->count(),
            'event_count' => $events->count(),
            'event_based_duration_seconds' => $this->elapsedSeconds(
                $events->first()?->occurred_at,
                $events->last()?->occurred_at,
            ),
            'distinct_pages' => $distinctPages,
            'primary_scenario_key' => $session->scenarioAssignment?->scenarioDefinition?->scenario_key,
            'primary_scenario_label' => $session->scenarioAssignment?->scenarioDefinition?->label,
            'secondary_scenarios' => $session->secondaryScenarioAssignments
                ->map(fn ($assignment) => [
                    'scenario_key' => $assignment->scenarioDefinition?->scenario_key,
                    'label' => $assignment->scenarioDefinition?->label,
                ])
                ->filter(fn (array $row) => filled($row['scenario_key']))
                ->values()
                ->all(),
            'journey_steps' => $eventSummaries,
            'truncated_events' => max($events->count() - $eventSummaries->count(), 0),
        ];
    }

    private function elapsedSeconds($start, $end): ?float
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
}
