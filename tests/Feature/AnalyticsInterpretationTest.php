<?php

namespace Tests\Feature;

use App\Models\Analytics\Conversion;
use App\Models\Analytics\Cta;
use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Page;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Models\LeadBox;
use App\Models\Popup;
use App\Services\Analytics\AnalyticsFunnelService;
use App\Services\Analytics\AnalyticsReportService;
use App\Services\Analytics\AnalyticsScenarioService;
use App\Services\Analytics\AnalyticsSessionJourneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsInterpretationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_service_assigns_explicit_primary_scenarios(): void
    {
        $scenarioService = app(AnalyticsScenarioService::class);
        $page = $this->createPage();
        $popup = Popup::factory()->create();
        $popupSession = $this->createSession($page);

        $this->createEventType('popup.impression');
        $this->createEventType('popup.opened');
        $this->createEventType('popup.submitted');
        $this->createEvent($popupSession, 'popup.impression', ['popup_id' => $popup->id]);
        $this->createEvent($popupSession, 'popup.opened', ['popup_id' => $popup->id]);
        $this->createEvent($popupSession, 'popup.submitted', ['popup_id' => $popup->id]);

        Conversion::query()->create([
            'session_id' => $popupSession->id,
            'visitor_id' => $popupSession->visitor_id,
            'conversion_type_id' => 2,
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'occurred_at' => $popupSession->started_at,
        ]);

        $popupAssignment = $scenarioService->assignSession($popupSession->fresh([
            'entryPage',
            'events.eventType',
            'conversions',
        ]));

        $this->assertSame('popup_assisted_conversion', $popupAssignment->scenarioDefinition->scenario_key);

        $lowSession = $this->createSession($page);
        $this->createEventType('page.view');
        $this->createEvent($lowSession, 'page.view', ['page_id' => $page->id]);

        $lowAssignment = $scenarioService->assignSession($lowSession->fresh([
            'entryPage',
            'events.eventType',
            'conversions',
        ]));

        $this->assertSame('low_engagement_no_conversion', $lowAssignment->scenarioDefinition->scenario_key);
    }

    public function test_session_journey_service_returns_ordered_compact_summary(): void
    {
        $page = $this->createPage();
        $cta = Cta::query()->create([
            'cta_key' => 'home.hero.consultation',
            'label' => 'Home Hero Consultation',
            'cta_type_id' => 2,
            'intent_key' => 'consultation',
            'is_active' => true,
        ]);
        $session = $this->createSession($page);

        $this->createEventType('page.view');
        $this->createEventType('cta.click');
        $this->createEvent($session, 'page.view', ['page_id' => $page->id], now()->subMinutes(3));
        $this->createEvent($session, 'cta.click', ['page_id' => $page->id, 'cta_id' => $cta->id], now()->subMinutes(1));

        $summary = app(AnalyticsSessionJourneyService::class)->summarizeSession($session->fresh([
            'entryPage',
            'events.eventType',
            'events.page',
            'events.cta',
            'conversions',
            'scenarioAssignment.scenarioDefinition',
            'secondaryScenarioAssignments.scenarioDefinition',
        ]));

        $this->assertSame($session->session_key, $summary['session_key']);
        $this->assertSame('Home', $summary['entry_page']);
        $this->assertFalse($summary['converted']);
        $this->assertCount(2, $summary['journey_steps']);
        $this->assertSame([], $summary['secondary_scenarios']);
        $this->assertSame('page.view', $summary['journey_steps'][0]['event_key']);
        $this->assertSame('cta.click', $summary['journey_steps'][1]['event_key']);
    }

    public function test_funnel_and_scenario_reports_are_grounded_in_session_data(): void
    {
        $page = $this->createPage();
        $cta = Cta::query()->create([
            'cta_key' => 'home.hero.consultation',
            'label' => 'Home Hero Consultation',
            'cta_type_id' => 2,
            'intent_key' => 'consultation',
            'is_active' => true,
        ]);
        $leadBox = LeadBox::factory()->active()->create();
        $scenarioService = app(AnalyticsScenarioService::class);

        $this->createEventType('page.view');
        $this->createEventType('cta.click');
        $this->createEventType('lead_box.impression');
        $this->createEventType('lead_box.click');
        $this->createEventType('lead_form.submitted');

        $convertedSession = $this->createSession($page);
        $this->createEvent($convertedSession, 'page.view', ['page_id' => $page->id]);
        $this->createEvent($convertedSession, 'cta.click', ['page_id' => $page->id, 'cta_id' => $cta->id]);
        $this->createEvent($convertedSession, 'lead_box.impression', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id]);
        $this->createEvent($convertedSession, 'lead_box.click', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id]);
        $this->createEvent($convertedSession, 'lead_form.submitted', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id]);

        Conversion::query()->create([
            'session_id' => $convertedSession->id,
            'visitor_id' => $convertedSession->visitor_id,
            'conversion_type_id' => 1,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'lead_box_id' => $leadBox->id,
            'occurred_at' => $convertedSession->started_at,
        ]);

        $dropOffSession = $this->createSession($page);
        $this->createEvent($dropOffSession, 'lead_box.impression', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id]);
        $this->createEvent($dropOffSession, 'lead_box.click', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id]);

        $scenarioService->assignRange(now()->subDay(), now());

        $funnels = app(AnalyticsFunnelService::class)->analyze(now()->subDay(), now());
        $leadBoxFunnel = $funnels->firstWhere('key', 'lead_box_capture');

        $this->assertSame(2, $leadBoxFunnel['steps'][0]['count']);
        $this->assertSame(2, $leadBoxFunnel['steps'][1]['count']);
        $this->assertSame(1, $leadBoxFunnel['steps'][2]['count']);
        $this->assertSame(1, $leadBoxFunnel['conversion_count']);

        $scenarioRows = app(AnalyticsReportService::class)->scenarioPerformance(now()->subDay(), now());
        $leadBoxScenario = $scenarioRows->firstWhere('scenario_key', 'lead_box_assisted_conversion');

        $this->assertNotNull($leadBoxScenario);
        $this->assertSame(1, $leadBoxScenario['sessions']);
        $this->assertSame(1, $leadBoxScenario['conversion_total']);
    }

    private function createPage(): Page
    {
        return Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);
    }

    private function createSession(Page $page): Session
    {
        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => now()->subMinutes(5),
            'entry_page_id' => $page->id,
        ]);
    }

    private function createEventType(string $eventKey): EventType
    {
        return EventType::query()->firstOrCreate(
            ['event_key' => $eventKey],
            [
                'label' => $eventKey,
                'category' => 'test',
            ],
        );
    }

    /**
     * @param  array<string, int|null>  $attributes
     */
    private function createEvent(Session $session, string $eventKey, array $attributes = [], $occurredAt = null): Event
    {
        $eventType = $this->createEventType($eventKey);
        $occurredAt = $occurredAt ?? now();

        return Event::query()->create([
            'visitor_id' => $session->visitor_id,
            'session_id' => $session->id,
            'event_type_id' => $eventType->id,
            'page_id' => $attributes['page_id'] ?? null,
            'cta_id' => $attributes['cta_id'] ?? null,
            'lead_box_id' => $attributes['lead_box_id'] ?? null,
            'popup_id' => $attributes['popup_id'] ?? null,
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ]);
    }
}
