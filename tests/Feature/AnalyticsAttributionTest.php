<?php

namespace Tests\Feature;

use App\Models\Analytics\AttributionTouch;
use App\Models\Analytics\Conversion;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Page;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Models\LeadBox;
use App\Models\Popup;
use App\Services\Analytics\AnalyticsAttributionService;
use App\Services\Analytics\AnalyticsReportService;
use App\Services\Analytics\AnalyticsRetentionService;
use App\Services\Analytics\AnalyticsScenarioService;
use App\Services\Analytics\RollupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAttributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-22 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_attribution_service_creates_first_last_and_conversion_touch_snapshots(): void
    {
        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);
        $popup = Popup::factory()->create(['name' => 'Attribution Popup']);
        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => '2026-04-21 09:00:00',
            'last_seen_at' => '2026-04-21 09:20:00',
        ]);
        $session = Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => '2026-04-21 09:00:00',
            'entry_page_id' => $page->id,
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'referrer_host' => 'google.com',
        ]);

        AttributionTouch::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'landing_page_id' => $page->id,
            'referrer_host' => 'google.com',
            'utm_source' => 'google',
            'utm_medium' => 'organic',
            'utm_campaign' => 'spring',
            'attribution_method' => 'observed',
            'attribution_confidence' => 1,
            'occurred_at' => '2026-04-21 09:00:00',
        ]);
        AttributionTouch::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'landing_page_id' => $page->id,
            'referrer_host' => 'mail.example.com',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'april',
            'attribution_method' => 'observed',
            'attribution_confidence' => 0.9,
            'occurred_at' => '2026-04-21 09:10:00',
        ]);

        $popupSubmitted = EventType::query()->create([
            'event_key' => 'popup.submitted',
            'label' => 'popup.submitted',
            'category' => 'test',
        ]);

        Event::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_type_id' => $popupSubmitted->id,
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'occurred_at' => '2026-04-21 09:14:00',
            'created_at' => '2026-04-21 09:14:00',
        ]);

        $conversion = Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'conversion_type_id' => 2,
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'occurred_at' => '2026-04-21 09:15:00',
        ]);

        $snapshots = app(AnalyticsAttributionService::class)->syncConversion($conversion);

        $this->assertCount(3, $snapshots);
        $this->assertDatabaseHas('analytics_conversion_attributions', [
            'conversion_id' => $conversion->id,
            'attribution_scope' => 'first_touch',
            'source_key' => 'google|organic|spring',
        ]);
        $this->assertDatabaseHas('analytics_conversion_attributions', [
            'conversion_id' => $conversion->id,
            'attribution_scope' => 'last_touch',
            'source_key' => 'newsletter|email|april',
        ]);
        $this->assertDatabaseHas('analytics_conversion_attributions', [
            'conversion_id' => $conversion->id,
            'attribution_scope' => 'conversion_touch',
            'source_key' => "popup:{$popup->id}",
        ]);

        DailyRollup::query()->create([
            'rollup_date' => '2026-04-21',
            'dimension_type' => RollupService::DIMENSION_TOTAL,
            'dimension_id' => null,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 1,
        ]);

        $summary = app(AnalyticsReportService::class)->attributionSummary(Carbon::parse('2026-04-21'), Carbon::parse('2026-04-21'));

        $this->assertSame(1, $summary['overview']['attributed_conversions']);
        $this->assertSame('newsletter / email', $summary['last_touch']->first()['source_label']);
        $this->assertSame('Attribution Popup', $summary['conversion_touch']->first()['source_label']);
    }

    public function test_scenario_service_supports_primary_and_secondary_assignments(): void
    {
        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);
        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => now()->subHour(),
            'last_seen_at' => now()->subHour(),
        ]);
        $leadBox = LeadBox::factory()->active()->create();
        $session = Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => now()->subHour(),
            'entry_page_id' => $page->id,
        ]);
        $this->createEvent($session, 'page.view');
        $this->createEvent($session, 'lead_box.click');
        $this->createEvent($session, 'lead_form.opened');
        $this->createEvent($session, 'lead_form.submitted');
        $this->createEvent($session, 'popup.impression');
        $this->createEvent($session, 'popup.opened');
        $this->createEvent($session, 'popup.dismissed');

        Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'conversion_type_id' => 1,
            'page_id' => $page->id,
            'lead_box_id' => $leadBox->id,
            'occurred_at' => now()->subMinutes(40),
        ]);

        $primary = app(AnalyticsScenarioService::class)->assignSession($session->fresh([
            'entryPage',
            'events.eventType',
            'events.page',
            'events.cta',
            'events.leadBox',
            'events.popup',
            'conversions',
        ]));

        $this->assertSame('lead_box_assisted_conversion', $primary->scenarioDefinition->scenario_key);
        $secondaryKeys = $session->fresh('secondaryScenarioAssignments.scenarioDefinition')
            ->secondaryScenarioAssignments
            ->pluck('scenarioDefinition.scenario_key')
            ->all();

        $this->assertContains('lead_box_assisted', $secondaryKeys);
        $this->assertContains('high_engagement', $secondaryKeys);
        $this->assertContains('research_heavy', $secondaryKeys);
    }

    public function test_retention_plan_is_non_destructive_and_reports_dependency_readiness(): void
    {
        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);
        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => '2025-01-01 00:00:00',
            'last_seen_at' => '2025-01-01 00:00:00',
        ]);
        $session = Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => '2025-01-01 00:00:00',
            'entry_page_id' => $page->id,
        ]);
        $eventType = EventType::query()->create([
            'event_key' => 'page.view',
            'label' => 'page.view',
            'category' => 'test',
        ]);
        Event::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_type_id' => $eventType->id,
            'page_id' => $page->id,
            'occurred_at' => '2025-01-01 00:05:00',
            'created_at' => '2025-01-01 00:05:00',
        ]);
        AttributionTouch::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'landing_page_id' => $page->id,
            'referrer_host' => 'example.com',
            'attribution_method' => 'observed',
            'attribution_confidence' => 1,
            'occurred_at' => '2025-01-01 00:00:00',
        ]);

        $plan = app(AnalyticsRetentionService::class)->plan(Carbon::parse('2026-04-22'));

        $this->assertGreaterThan(0, $plan['eligible_counts']['raw_sessions']);
        $this->assertGreaterThan(0, $plan['eligible_counts']['raw_events']);
        $this->assertGreaterThan(0, $plan['eligible_counts']['raw_touches']);

        $this->artisan('analytics:retention:plan', ['--date' => '2026-04-22'])
            ->assertSuccessful()
            ->expectsOutputToContain('Analytics retention plan as of 2026-04-22')
            ->expectsOutputToContain('Eligible raw data');
    }

    private function createEvent(Session $session, string $eventKey): Event
    {
        $eventType = EventType::query()->firstOrCreate(
            ['event_key' => $eventKey],
            [
                'label' => $eventKey,
                'category' => 'test',
            ],
        );

        return Event::query()->create([
            'visitor_id' => $session->visitor_id,
            'session_id' => $session->id,
            'event_type_id' => $eventType->id,
            'page_id' => $session->entry_page_id,
            'occurred_at' => now()->subMinutes(50),
            'created_at' => now()->subMinutes(50),
        ]);
    }
}
