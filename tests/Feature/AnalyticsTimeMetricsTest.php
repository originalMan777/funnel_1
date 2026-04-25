<?php

namespace Tests\Feature;

use App\Models\Analytics\Conversion;
use App\Models\Analytics\Cta;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Page;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Models\LeadBox;
use App\Models\Popup;
use App\Services\Analytics\AnalyticsFunnelService;
use App\Services\Analytics\AnalyticsReportService;
use App\Services\Analytics\AnalyticsSessionJourneyService;
use App\Services\Analytics\RollupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTimeMetricsTest extends TestCase
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

    public function test_report_service_derives_light_event_based_time_metrics(): void
    {
        $reportService = app(AnalyticsReportService::class);
        $page = $this->createPage();
        $cta = $this->createCta();
        $leadBox = LeadBox::factory()->active()->create();
        $popup = Popup::factory()->create();
        $date = Carbon::parse('2026-04-20');

        $sessionOne = $this->createSession($page, '2026-04-20 10:00:00');
        $this->createEvent($sessionOne, 'page.view', ['page_id' => $page->id], '2026-04-20 10:00:10');
        $this->createEvent($sessionOne, 'cta.impression', ['page_id' => $page->id, 'cta_id' => $cta->id], '2026-04-20 10:00:20');
        $this->createEvent($sessionOne, 'cta.click', ['page_id' => $page->id, 'cta_id' => $cta->id], '2026-04-20 10:01:10');
        $this->createEvent($sessionOne, 'lead_box.impression', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id], '2026-04-20 10:01:20');
        $this->createEvent($sessionOne, 'lead_box.click', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id], '2026-04-20 10:01:50');
        $this->createEvent($sessionOne, 'lead_form.submitted', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id], '2026-04-20 10:02:20');
        $this->createEvent($sessionOne, 'popup.impression', ['page_id' => $page->id, 'popup_id' => $popup->id], '2026-04-20 10:02:40');
        $this->createEvent($sessionOne, 'popup.opened', ['page_id' => $page->id, 'popup_id' => $popup->id], '2026-04-20 10:02:50');
        $this->createEvent($sessionOne, 'popup.dismissed', ['page_id' => $page->id, 'popup_id' => $popup->id], '2026-04-20 10:03:20');
        $this->createConversion($sessionOne, [
            'conversion_type_id' => 1,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'lead_box_id' => $leadBox->id,
            'occurred_at' => '2026-04-20 10:03:40',
        ]);

        $sessionTwo = $this->createSession($page, '2026-04-20 11:00:00');
        $this->createEvent($sessionTwo, 'page.view', ['page_id' => $page->id], '2026-04-20 11:00:00');
        $this->createEvent($sessionTwo, 'cta.impression', ['page_id' => $page->id, 'cta_id' => $cta->id], '2026-04-20 11:00:10');
        $this->createEvent($sessionTwo, 'cta.click', ['page_id' => $page->id, 'cta_id' => $cta->id], '2026-04-20 11:00:20');
        $this->createEvent($sessionTwo, 'popup.impression', ['page_id' => $page->id, 'popup_id' => $popup->id], '2026-04-20 11:00:30');
        $this->createEvent($sessionTwo, 'popup.opened', ['page_id' => $page->id, 'popup_id' => $popup->id], '2026-04-20 11:00:40');
        $this->createEvent($sessionTwo, 'popup.submitted', ['page_id' => $page->id, 'popup_id' => $popup->id], '2026-04-20 11:01:20');
        $this->createConversion($sessionTwo, [
            'conversion_type_id' => 2,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'popup_id' => $popup->id,
            'occurred_at' => '2026-04-20 11:01:40',
        ]);

        $this->createRollup($date, RollupService::DIMENSION_PAGE, $page->id, RollupService::METRIC_PAGE_VIEWS, 2);
        $this->createRollup($date, RollupService::DIMENSION_PAGE, $page->id, RollupService::METRIC_PAGE_CONVERSIONS, 2);
        $this->createRollup($date, RollupService::DIMENSION_CTA, $cta->id, RollupService::METRIC_CTA_IMPRESSIONS, 2);
        $this->createRollup($date, RollupService::DIMENSION_CTA, $cta->id, RollupService::METRIC_CTA_CLICKS, 2);
        $this->createRollup($date, RollupService::DIMENSION_CTA, $cta->id, RollupService::METRIC_CTA_CONVERSIONS, 2);
        $this->createRollup($date, RollupService::DIMENSION_LEAD_BOX, $leadBox->id, RollupService::METRIC_LEAD_BOX_IMPRESSIONS, 1);
        $this->createRollup($date, RollupService::DIMENSION_LEAD_BOX, $leadBox->id, RollupService::METRIC_LEAD_BOX_CLICKS, 1);
        $this->createRollup($date, RollupService::DIMENSION_LEAD_BOX, $leadBox->id, RollupService::METRIC_LEAD_FORM_SUBMISSIONS, 1);
        $this->createRollup($date, RollupService::DIMENSION_POPUP, $popup->id, RollupService::METRIC_POPUP_IMPRESSIONS, 2);
        $this->createRollup($date, RollupService::DIMENSION_POPUP, $popup->id, RollupService::METRIC_POPUP_OPENS, 2);
        $this->createRollup($date, RollupService::DIMENSION_POPUP, $popup->id, RollupService::METRIC_POPUP_DISMISSALS, 1);
        $this->createRollup($date, RollupService::DIMENSION_POPUP, $popup->id, RollupService::METRIC_POPUP_SUBMISSIONS, 1);
        $this->createRollup($date, RollupService::DIMENSION_TOTAL, null, RollupService::METRIC_CONVERSION_TOTAL, 2);
        $this->createRollup($date, RollupService::DIMENSION_CONVERSION_TYPE, 1, RollupService::METRIC_CONVERSION_TOTAL, 1);
        $this->createRollup($date, RollupService::DIMENSION_CONVERSION_TYPE, 2, RollupService::METRIC_CONVERSION_TOTAL, 1);

        $overview = $reportService->overviewSummary($date, $date);
        $pageRow = $reportService->pagePerformance($date, $date)->firstWhere('id', $page->id);
        $ctaRow = $reportService->ctaPerformance($date, $date)->firstWhere('id', $cta->id);
        $leadBoxRow = $reportService->leadBoxPerformance($date, $date)->firstWhere('id', $leadBox->id);
        $popupRow = $reportService->popupPerformance($date, $date)->firstWhere('id', $popup->id);
        $conversionSummary = $reportService->conversionSummary($date, $date);

        $this->assertSame(135.0, $overview['average_session_duration_seconds']);
        $this->assertSame(160.0, $overview['average_time_to_conversion_seconds']);
        $this->assertSame(40.0, $pageRow['avg_time_to_cta_click_seconds']);
        $this->assertSame(155.0, $pageRow['avg_time_to_conversion_seconds']);
        $this->assertSame(40.0, $ctaRow['avg_time_to_click_seconds']);
        $this->assertSame(115.0, $ctaRow['avg_click_to_conversion_seconds']);
        $this->assertSame(60.0, $leadBoxRow['avg_impression_to_submit_seconds']);
        $this->assertSame(30.0, $leadBoxRow['avg_click_to_submit_seconds']);
        $this->assertSame(40.0, $popupRow['avg_open_to_submit_seconds']);
        $this->assertSame(30.0, $popupRow['avg_open_to_dismiss_seconds']);
        $this->assertSame(160.0, $conversionSummary['average_time_to_conversion_seconds']);
    }

    public function test_funnel_and_session_journey_services_surface_elapsed_timing(): void
    {
        $page = $this->createPage();
        $cta = $this->createCta();
        $leadBox = LeadBox::factory()->active()->create();
        $date = Carbon::parse('2026-04-21');
        $session = $this->createSession($page, '2026-04-21 09:00:00');

        $this->createEvent($session, 'page.view', ['page_id' => $page->id], '2026-04-21 09:00:00');
        $this->createEvent($session, 'cta.click', ['page_id' => $page->id, 'cta_id' => $cta->id], '2026-04-21 09:00:30');
        $this->createEvent($session, 'lead_form.submitted', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id], '2026-04-21 09:02:00');
        $this->createConversion($session, [
            'conversion_type_id' => 1,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'lead_box_id' => $leadBox->id,
            'occurred_at' => '2026-04-21 09:03:30',
        ]);

        $funnel = app(AnalyticsFunnelService::class)
            ->analyze($date, $date)
            ->firstWhere('key', 'page_to_cta_to_lead_to_conversion');
        $journey = app(AnalyticsSessionJourneyService::class)->summarizeSession($session->fresh([
            'entryPage',
            'events.eventType',
            'events.page',
            'events.cta',
            'events.leadBox',
            'conversions',
            'scenarioAssignment.scenarioDefinition',
        ]));

        $this->assertSame(210.0, $funnel['average_elapsed_seconds']);
        $this->assertSame(30.0, $funnel['step_timings'][0]['average_elapsed_seconds']);
        $this->assertSame(90.0, $funnel['step_timings'][1]['average_elapsed_seconds']);
        $this->assertSame(90.0, $funnel['step_timings'][2]['average_elapsed_seconds']);
        $this->assertSame(120.0, $journey['event_based_duration_seconds']);
        $this->assertSame(30.0, $journey['journey_steps'][1]['elapsed_from_first_event_seconds']);
        $this->assertSame(120.0, $journey['journey_steps'][2]['elapsed_from_first_event_seconds']);
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

    private function createCta(): Cta
    {
        return Cta::query()->create([
            'cta_key' => 'home.hero.consultation',
            'label' => 'Home Hero Consultation',
            'cta_type_id' => 2,
            'intent_key' => 'consultation',
            'is_active' => true,
        ]);
    }

    private function createSession(Page $page, string $startedAt): Session
    {
        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => $startedAt,
            'last_seen_at' => $startedAt,
        ]);

        return Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => $startedAt,
            'entry_page_id' => $page->id,
        ]);
    }

    /**
     * @param  array<string, int|null>  $attributes
     */
    private function createEvent(Session $session, string $eventKey, array $attributes, string $occurredAt): Event
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
            'page_id' => $attributes['page_id'] ?? null,
            'cta_id' => $attributes['cta_id'] ?? null,
            'lead_box_id' => $attributes['lead_box_id'] ?? null,
            'popup_id' => $attributes['popup_id'] ?? null,
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ]);
    }

    /**
     * @param  array<string, int|string|null>  $attributes
     */
    private function createConversion(Session $session, array $attributes): Conversion
    {
        return Conversion::query()->create([
            'session_id' => $session->id,
            'visitor_id' => $session->visitor_id,
            'conversion_type_id' => $attributes['conversion_type_id'],
            'page_id' => $attributes['page_id'] ?? null,
            'cta_id' => $attributes['cta_id'] ?? null,
            'lead_box_id' => $attributes['lead_box_id'] ?? null,
            'popup_id' => $attributes['popup_id'] ?? null,
            'occurred_at' => $attributes['occurred_at'],
        ]);
    }

    private function createRollup(Carbon $date, string $dimensionType, ?int $dimensionId, string $metricKey, int $metricValue): DailyRollup
    {
        return DailyRollup::query()->create([
            'rollup_date' => $date->toDateString(),
            'dimension_type' => $dimensionType,
            'dimension_id' => $dimensionId,
            'metric_key' => $metricKey,
            'metric_value' => $metricValue,
        ]);
    }
}
