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
use App\Services\Analytics\AnalyticsReportService;
use App\Services\Analytics\RollupService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsRollupTest extends TestCase
{
    use RefreshDatabase;

    public function test_rollup_generation_aggregates_first_wave_metrics(): void
    {
        $date = now()->startOfDay()->subDay();

        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => $date,
            'last_seen_at' => $date,
        ]);

        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);

        $cta = Cta::query()->create([
            'cta_key' => 'home.hero.consultation',
            'label' => 'Home Hero Consultation',
            'cta_type_id' => 2,
            'intent_key' => 'consultation',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => $date,
            'entry_page_id' => $page->id,
        ]);

        $leadBox = LeadBox::factory()->active()->create();
        $popup = Popup::factory()->create();

        $eventTypes = collect([
            'page.view',
            'cta.impression',
            'cta.click',
            'lead_box.impression',
            'lead_box.click',
            'lead_form.submitted',
            'lead_form.failed',
            'popup.eligible',
            'popup.impression',
            'popup.opened',
            'popup.dismissed',
            'popup.submitted',
        ])->mapWithKeys(fn (string $eventKey) => [
            $eventKey => EventType::query()->create([
                'event_key' => $eventKey,
                'label' => $eventKey,
                'category' => 'test',
            ])->id,
        ]);

        $this->createEvent($eventTypes['page.view'], $date, $visitor->id, $session->id, $page->id);
        $this->createEvent($eventTypes['page.view'], $date, $visitor->id, $session->id, $page->id);
        $this->createEvent($eventTypes['cta.impression'], $date, $visitor->id, $session->id, $page->id, $cta->id);
        $this->createEvent($eventTypes['cta.click'], $date, $visitor->id, $session->id, $page->id, $cta->id);
        $this->createEvent($eventTypes['lead_box.impression'], $date, $visitor->id, $session->id, $page->id, null, $leadBox->id);
        $this->createEvent($eventTypes['lead_box.click'], $date, $visitor->id, $session->id, $page->id, null, $leadBox->id);
        $this->createEvent($eventTypes['lead_form.submitted'], $date, $visitor->id, $session->id, $page->id, null, $leadBox->id);
        $this->createEvent($eventTypes['lead_form.failed'], $date, $visitor->id, $session->id, $page->id, null, $leadBox->id);
        $this->createEvent($eventTypes['popup.eligible'], $date, $visitor->id, $session->id, $page->id, null, null, $popup->id);
        $this->createEvent($eventTypes['popup.impression'], $date, $visitor->id, $session->id, $page->id, null, null, $popup->id);
        $this->createEvent($eventTypes['popup.opened'], $date, $visitor->id, $session->id, $page->id, null, null, $popup->id);
        $this->createEvent($eventTypes['popup.dismissed'], $date, $visitor->id, $session->id, $page->id, null, null, $popup->id);
        $this->createEvent($eventTypes['popup.submitted'], $date, $visitor->id, $session->id, $page->id, null, null, $popup->id);

        Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'conversion_type_id' => 1,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'lead_box_id' => $leadBox->id,
            'popup_id' => $popup->id,
            'occurred_at' => $date,
            'properties' => ['source' => 'test'],
        ]);

        app(RollupService::class)->generateForDate($date);

        $this->assertRollupValue($date, RollupService::DIMENSION_PAGE, $page->id, RollupService::METRIC_PAGE_VIEWS, 2.0);
        $this->assertRollupValue($date, RollupService::DIMENSION_CTA, $cta->id, RollupService::METRIC_CTA_CLICKS, 1.0);
        $this->assertRollupValue($date, RollupService::DIMENSION_LEAD_BOX, $leadBox->id, RollupService::METRIC_LEAD_FORM_SUBMISSIONS, 1.0);
        $this->assertRollupValue($date, RollupService::DIMENSION_POPUP, $popup->id, RollupService::METRIC_POPUP_SUBMISSIONS, 1.0);
        $this->assertRollupValue($date, RollupService::DIMENSION_TOTAL, null, RollupService::METRIC_CONVERSION_TOTAL, 1.0);
        $this->assertRollupValue($date, RollupService::DIMENSION_CONVERSION_TYPE, 1, RollupService::METRIC_CONVERSION_TOTAL, 1.0);
    }

    public function test_rollup_generation_is_idempotent_when_rerun(): void
    {
        $date = now()->startOfDay()->subDay();

        DailyRollup::query()->create([
            'rollup_date' => $date->toDateString(),
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => 99,
            'metric_key' => RollupService::METRIC_PAGE_VIEWS,
            'metric_value' => 9,
        ]);

        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);

        $eventType = EventType::query()->create([
            'event_key' => 'page.view',
            'label' => 'Page View',
            'category' => 'navigation',
        ]);

        Event::query()->create([
            'event_type_id' => $eventType->id,
            'page_id' => $page->id,
            'occurred_at' => $date,
            'created_at' => $date,
        ]);

        $service = app(RollupService::class);

        $service->generateForDate($date);
        $service->generateForDate($date);

        $this->assertDatabaseMissing('analytics_daily_rollups', [
            'rollup_date' => $date->toDateString(),
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => 99,
            'metric_key' => RollupService::METRIC_PAGE_VIEWS,
        ]);

        $this->assertSame(
            1,
            DailyRollup::query()
                ->whereDate('rollup_date', $date->toDateString())
                ->where('dimension_type', RollupService::DIMENSION_PAGE)
                ->where('dimension_id', $page->id)
                ->where('metric_key', RollupService::METRIC_PAGE_VIEWS)
                ->count(),
        );
    }

    public function test_report_service_summarizes_rollups_by_range(): void
    {
        $service = app(AnalyticsReportService::class);
        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();

        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);

        $cta = Cta::query()->create([
            'cta_key' => 'home.hero.consultation',
            'label' => 'Home Hero Consultation',
            'cta_type_id' => 2,
            'intent_key' => 'consultation',
            'is_active' => true,
        ]);

        $leadBox = LeadBox::factory()->active()->create([
            'internal_name' => 'report-lead-box',
            'title' => 'Report Lead Box',
        ]);

        $popup = Popup::factory()->create([
            'name' => 'Report Popup',
            'slug' => 'report-popup',
        ]);

        $this->storeRollup($yesterday, RollupService::DIMENSION_PAGE, $page->id, RollupService::METRIC_PAGE_VIEWS, 3);
        $this->storeRollup($today, RollupService::DIMENSION_PAGE, $page->id, RollupService::METRIC_PAGE_CONVERSIONS, 2);
        $this->storeRollup($today, RollupService::DIMENSION_CTA, $cta->id, RollupService::METRIC_CTA_IMPRESSIONS, 5);
        $this->storeRollup($today, RollupService::DIMENSION_CTA, $cta->id, RollupService::METRIC_CTA_CLICKS, 2);
        $this->storeRollup($today, RollupService::DIMENSION_CTA, $cta->id, RollupService::METRIC_CTA_CONVERSIONS, 1);
        $this->storeRollup($today, RollupService::DIMENSION_LEAD_BOX, $leadBox->id, RollupService::METRIC_LEAD_FORM_SUBMISSIONS, 4);
        $this->storeRollup($today, RollupService::DIMENSION_POPUP, $popup->id, RollupService::METRIC_POPUP_OPENS, 6);
        $this->storeRollup($today, RollupService::DIMENSION_TOTAL, null, RollupService::METRIC_CONVERSION_TOTAL, 7);
        $this->storeRollup($today, RollupService::DIMENSION_CONVERSION_TYPE, 2, RollupService::METRIC_CONVERSION_TOTAL, 3);

        $pages = $service->pagePerformance($yesterday, $today);
        $ctas = $service->ctaPerformance($yesterday, $today);
        $leadBoxes = $service->leadBoxPerformance($yesterday, $today);
        $popups = $service->popupPerformance($yesterday, $today);
        $conversions = $service->conversionSummary($yesterday, $today);
        $overview = $service->overviewSummary($yesterday, $today);
        $trend = $service->overviewTrend($yesterday, $today);

        $this->assertSame(3.0, $pages->first()['views']);
        $this->assertSame(2.0, $pages->first()['conversions']);
        $this->assertSame(66.67, $pages->first()['conversion_rate']);
        $this->assertSame(5.0, $ctas->first()['impressions']);
        $this->assertSame(1.0, $ctas->first()['conversions']);
        $this->assertSame(40.0, $ctas->first()['ctr']);
        $this->assertSame(50.0, $ctas->first()['conversion_rate']);
        $this->assertSame(4.0, $leadBoxes->first()['submissions']);
        $this->assertSame(6.0, $popups->first()['opens']);
        $this->assertSame(7.0, $overview['conversions']);
        $this->assertSame(2.0, $overview['cta_clicks']);
        $this->assertSame(2, $trend->count());
        $this->assertSame(7.0, $conversions['total']->first()['metric_value']);
        $this->assertSame(2, $conversions['by_type']->first()['conversion_type_id']);
        $this->assertSame('Popup Submission', $conversions['by_type']->first()['label']);
        $this->assertSame(3.0, $conversions['by_type']->first()['total']);
    }

    public function test_rollup_command_supports_single_date_and_range_backfill(): void
    {
        $firstDate = now()->startOfDay()->subDays(2);
        $secondDate = now()->startOfDay()->subDay();

        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);

        $eventType = EventType::query()->create([
            'event_key' => 'page.view',
            'label' => 'Page View',
            'category' => 'navigation',
        ]);

        Event::query()->create([
            'event_type_id' => $eventType->id,
            'page_id' => $page->id,
            'occurred_at' => $firstDate,
            'created_at' => $firstDate,
        ]);

        Event::query()->create([
            'event_type_id' => $eventType->id,
            'page_id' => $page->id,
            'occurred_at' => $secondDate,
            'created_at' => $secondDate,
        ]);

        $this->artisan('analytics:rollups', [
            '--date' => $firstDate->toDateString(),
        ])->assertSuccessful();

        $this->artisan('analytics:rollups', [
            '--from' => $firstDate->toDateString(),
            '--to' => $secondDate->toDateString(),
        ])->assertSuccessful();

        $this->assertRollupValue($firstDate, RollupService::DIMENSION_PAGE, $page->id, RollupService::METRIC_PAGE_VIEWS, 1.0);
        $this->assertRollupValue($secondDate, RollupService::DIMENSION_PAGE, $page->id, RollupService::METRIC_PAGE_VIEWS, 1.0);
    }

    private function createEvent(
        int $eventTypeId,
        CarbonInterface $occurredAt,
        ?int $visitorId = null,
        ?int $sessionId = null,
        ?int $pageId = null,
        ?int $ctaId = null,
        ?int $leadBoxId = null,
        ?int $popupId = null,
    ): void {
        Event::query()->create([
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'event_type_id' => $eventTypeId,
            'page_id' => $pageId,
            'cta_id' => $ctaId,
            'lead_box_id' => $leadBoxId,
            'popup_id' => $popupId,
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ]);
    }

    private function storeRollup(
        CarbonInterface $date,
        string $dimensionType,
        ?int $dimensionId,
        string $metricKey,
        int $metricValue,
    ): void {
        DailyRollup::query()->create([
            'rollup_date' => $date->toDateString(),
            'dimension_type' => $dimensionType,
            'dimension_id' => $dimensionId,
            'metric_key' => $metricKey,
            'metric_value' => $metricValue,
        ]);
    }

    private function assertRollupValue(
        CarbonInterface $date,
        string $dimensionType,
        ?int $dimensionId,
        string $metricKey,
        float $expected,
    ): void {
        $rollup = DailyRollup::query()
            ->whereDate('rollup_date', $date->toDateString())
            ->where('dimension_type', $dimensionType)
            ->where('metric_key', $metricKey)
            ->where('dimension_id', $dimensionId)
            ->first();

        $this->assertNotNull($rollup);
        $this->assertSame($expected, (float) $rollup->metric_value);
    }
}
