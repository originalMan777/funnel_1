<?php

namespace App\Services\Analytics;

use App\Models\Analytics\ScenarioDefinition;
use App\Models\Analytics\Session;
use App\Models\Analytics\SessionScenario;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AnalyticsScenarioService
{
    /**
     * @return Collection<int, ScenarioDefinition>
     */
    public function syncDefinitions(): Collection
    {
        $definitions = collect();

        foreach ($this->configuredDefinitions() as $definition) {
            $definitions->push(ScenarioDefinition::query()->updateOrCreate(
                ['scenario_key' => $definition['scenario_key']],
                [
                    'label' => $definition['label'],
                    'description' => $definition['description'] ?? null,
                    'priority' => $definition['priority'] ?? 100,
                    'is_active' => true,
                ],
            ));
        }

        return $definitions;
    }

    /**
     * @return Collection<int, SessionScenario>
     */
    public function assignRange(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $this->syncDefinitions();

        return Session::query()
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
            ])
            ->orderBy('started_at')
            ->get()
            ->flatMap(function (Session $session): Collection {
                $this->assignSession($session);

                return $session->fresh('scenarioAssignments.scenarioDefinition')->scenarioAssignments;
            })
            ->values();
    }

    public function assignSession(Session $session): SessionScenario
    {
        $this->syncDefinitions();

        $classification = $this->classify($session);
        $definitions = ScenarioDefinition::query()
            ->whereIn('scenario_key', array_merge(
                [$classification['primary_scenario_key']],
                $classification['secondary_scenario_keys'],
            ))
            ->get()
            ->keyBy('scenario_key');

        SessionScenario::query()
            ->where('session_id', $session->id)
            ->where('assignment_type', SessionScenario::TYPE_PRIMARY)
            ->delete();

        $primaryDefinition = $definitions->get($classification['primary_scenario_key']);

        $primaryAssignment = SessionScenario::query()->create([
            'session_id' => $session->id,
            'scenario_definition_id' => $primaryDefinition->id,
            'assignment_type' => SessionScenario::TYPE_PRIMARY,
            'assigned_at' => $session->started_at ?? now(),
            'evidence' => $classification['evidence'],
        ]);

        $secondaryDefinitionIds = collect($classification['secondary_scenario_keys'])
            ->map(fn (string $key) => $definitions->get($key)?->id)
            ->filter()
            ->values();

        SessionScenario::query()
            ->where('session_id', $session->id)
            ->where('assignment_type', SessionScenario::TYPE_SECONDARY)
            ->when($secondaryDefinitionIds->isNotEmpty(), fn ($query) => $query->whereNotIn('scenario_definition_id', $secondaryDefinitionIds->all()))
            ->when($secondaryDefinitionIds->isEmpty(), fn ($query) => $query)
            ->delete();

        foreach ($secondaryDefinitionIds as $definitionId) {
            SessionScenario::query()->updateOrCreate(
                [
                    'session_id' => $session->id,
                    'scenario_definition_id' => $definitionId,
                    'assignment_type' => SessionScenario::TYPE_SECONDARY,
                ],
                [
                    'assigned_at' => $session->started_at ?? now(),
                    'evidence' => $classification['evidence'],
                ],
            );
        }

        return $primaryAssignment->fresh('scenarioDefinition');
    }

    /**
     * @return array{
     *     primary_scenario_key:string,
     *     secondary_scenario_keys:array<int, string>,
     *     evidence:array<string,mixed>
     * }
     */
    public function classify(Session $session): array
    {
        $snapshot = $this->sessionSnapshot($session);

        $thresholds = [
            'high_engagement_min_meaningful_events' => (int) config('analytics.scenarios.thresholds.high_engagement_min_meaningful_events', 2),
            'research_min_distinct_pages' => (int) config('analytics.scenarios.thresholds.research_min_distinct_pages', 2),
            'research_min_key_actions' => (int) config('analytics.scenarios.thresholds.research_min_key_actions', 3),
            'repeat_interaction_min_repeats' => (int) config('analytics.scenarios.thresholds.repeat_interaction_min_repeats', 2),
        ];

        $converted = $snapshot['converted'];
        $popupAssisted = $snapshot['popup_submitted'] > 0 || $snapshot['conversion_popup_count'] > 0;
        $leadBoxAssisted = $snapshot['lead_form_submitted'] > 0 || $snapshot['conversion_lead_box_count'] > 0;
        $repeatedInteractions = max(
            $snapshot['cta_clicks'],
            $snapshot['lead_box_clicks'],
            $snapshot['popup_opens'],
            $snapshot['lead_form_opened'],
        ) >= $thresholds['repeat_interaction_min_repeats'];
        $researchHeavy = $snapshot['distinct_pages'] >= $thresholds['research_min_distinct_pages']
            || $snapshot['key_actions'] >= $thresholds['research_min_key_actions'];
        $researchBeforeConversion = $converted && $researchHeavy;
        $directCtaConversion = $converted
            && $snapshot['cta_clicks'] > 0
            && ! $popupAssisted
            && ! $leadBoxAssisted;
        $popupResistant = ! $converted
            && ($snapshot['popup_impressions'] > 0 || $snapshot['popup_opens'] > 0)
            && $snapshot['popup_dismissals'] > 0
            && $snapshot['popup_submitted'] === 0;
        $highEngagement = $snapshot['meaningful_events'] >= $thresholds['high_engagement_min_meaningful_events'];
        $highEngagementNoConversion = ! $converted && $highEngagement;
        $lowEngagementNoConversion = ! $converted
            && $snapshot['meaningful_events'] === 0
            && $snapshot['event_count'] <= 1;

        $scenarioKey = match (true) {
            $popupAssisted => 'popup_assisted_conversion',
            $leadBoxAssisted => 'lead_box_assisted_conversion',
            $converted && $repeatedInteractions => 'repeat_interaction_before_conversion',
            $researchBeforeConversion => 'research_then_conversion',
            $directCtaConversion => 'direct_cta_conversion',
            $converted => 'direct_conversion_no_assist',
            $popupResistant => 'popup_resistant_session',
            $highEngagementNoConversion => 'high_engagement_no_conversion',
            default => 'low_engagement_no_conversion',
        };

        $secondaryKeys = collect([
            $popupResistant ? 'popup_resistant' : null,
            $highEngagement ? 'high_engagement' : null,
            $leadBoxAssisted ? 'lead_box_assisted' : null,
            $repeatedInteractions ? 'repeat_interaction' : null,
            $researchHeavy ? 'research_heavy' : null,
        ])
            ->filter()
            ->reject(fn (string $key) => $key === $scenarioKey)
            ->values()
            ->all();

        return [
            'primary_scenario_key' => $scenarioKey,
            'secondary_scenario_keys' => $secondaryKeys,
            'evidence' => [
                ...$snapshot,
                'rule' => $scenarioKey,
                'secondary_rules' => $secondaryKeys,
            ],
        ];
    }

    /**
     * @return array<string, int|bool|string|null>
     */
    private function sessionSnapshot(Session $session): array
    {
        $session->loadMissing([
            'entryPage',
            'events.eventType',
            'events.page',
            'events.cta',
            'events.leadBox',
            'events.popup',
            'conversions',
        ]);

        $events = $session->events
            ->sortBy(fn ($event) => sprintf('%s-%010d', optional($event->occurred_at)?->format('YmdHis.u'), $event->id))
            ->values();
        $eventKeys = $events
            ->map(fn ($event) => $event->eventType?->event_key)
            ->filter()
            ->values();

        $eventCounts = $eventKeys
            ->countBy();

        $distinctPages = collect([$session->entry_page_id])
            ->merge($events->pluck('page_id'))
            ->filter()
            ->unique()
            ->count();

        $meaningfulEvents = collect([
            'cta.click',
            'lead_box.click',
            'lead_form.opened',
            'lead_form.submitted',
            'lead_form.failed',
            'popup.impression',
            'popup.opened',
            'popup.dismissed',
            'popup.submitted',
        ])->sum(fn (string $key) => (int) ($eventCounts[$key] ?? 0));

        $keyActions = collect([
            'cta.click',
            'lead_box.click',
            'lead_form.submitted',
            'popup.opened',
            'popup.dismissed',
            'popup.submitted',
        ])->sum(fn (string $key) => (int) ($eventCounts[$key] ?? 0));

        return [
            'converted' => $session->conversions->isNotEmpty(),
            'event_count' => $events->count(),
            'conversion_count' => $session->conversions->count(),
            'distinct_pages' => $distinctPages,
            'meaningful_events' => $meaningfulEvents,
            'key_actions' => $keyActions,
            'cta_clicks' => (int) ($eventCounts['cta.click'] ?? 0),
            'lead_box_clicks' => (int) ($eventCounts['lead_box.click'] ?? 0),
            'lead_form_opened' => (int) ($eventCounts['lead_form.opened'] ?? 0),
            'lead_form_submitted' => (int) ($eventCounts['lead_form.submitted'] ?? 0),
            'popup_impressions' => (int) ($eventCounts['popup.impression'] ?? 0),
            'popup_opens' => (int) ($eventCounts['popup.opened'] ?? 0),
            'popup_dismissals' => (int) ($eventCounts['popup.dismissed'] ?? 0),
            'popup_submitted' => (int) ($eventCounts['popup.submitted'] ?? 0),
            'conversion_popup_count' => $session->conversions->filter(fn ($conversion) => $conversion->popup_id !== null)->count(),
            'conversion_lead_box_count' => $session->conversions->filter(fn ($conversion) => $conversion->lead_box_id !== null)->count(),
            'entry_page' => $session->entryPage?->page_key,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function configuredDefinitions(): Collection
    {
        return collect(config('analytics.scenarios.definitions', []))
            ->merge(config('analytics.scenarios.secondary_definitions', []))
            ->values();
    }
}
