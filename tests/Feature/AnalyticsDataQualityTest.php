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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDataQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_wave_analytics_records_are_rollup_ready(): void
    {
        $occurredAt = now()->subHour();

        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => $occurredAt,
            'last_seen_at' => $occurredAt,
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
            'started_at' => $occurredAt,
            'entry_page_id' => $page->id,
        ]);

        $leadBox = LeadBox::factory()->active()->create();
        $popup = Popup::factory()->create();

        $pageViewType = EventType::query()->create([
            'event_key' => 'page.view',
            'label' => 'Page View',
            'category' => 'navigation',
        ]);

        $ctaClickType = EventType::query()->create([
            'event_key' => 'cta.click',
            'label' => 'CTA Click',
            'category' => 'engagement',
        ]);

        $popupType = EventType::query()->create([
            'event_key' => 'popup.impression',
            'label' => 'Popup Impression',
            'category' => 'popup',
        ]);

        $leadBoxType = EventType::query()->create([
            'event_key' => 'lead_box.impression',
            'label' => 'Lead Box Impression',
            'category' => 'lead_capture',
        ]);

        Event::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_type_id' => $pageViewType->id,
            'page_id' => $page->id,
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ]);

        Event::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_type_id' => $ctaClickType->id,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ]);

        Event::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_type_id' => $leadBoxType->id,
            'page_id' => $page->id,
            'lead_box_id' => $leadBox->id,
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ]);

        Event::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_type_id' => $popupType->id,
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ]);

        Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'conversion_type_id' => 1,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'lead_box_id' => $leadBox->id,
            'popup_id' => $popup->id,
            'occurred_at' => $occurredAt,
        ]);

        $pageView = Event::query()->where('event_type_id', $pageViewType->id)->firstOrFail();
        $ctaClick = Event::query()->where('event_type_id', $ctaClickType->id)->firstOrFail();
        $leadBoxImpression = Event::query()->where('event_type_id', $leadBoxType->id)->firstOrFail();
        $popupImpression = Event::query()->where('event_type_id', $popupType->id)->firstOrFail();
        $conversion = Conversion::query()->firstOrFail();

        $this->assertNotNull($pageView->visitor_id);
        $this->assertNotNull($pageView->session_id);
        $this->assertNotNull($pageView->page_id);
        $this->assertNotNull($ctaClick->cta_id);
        $this->assertNotNull($leadBoxImpression->lead_box_id);
        $this->assertNotNull($popupImpression->popup_id);
        $this->assertNotNull($conversion->conversion_type_id);
        $this->assertNotNull($conversion->page_id);
        $this->assertTrue($pageView->occurred_at->isSameDay($occurredAt));
        $this->assertTrue($conversion->occurred_at->isSameDay($occurredAt));
    }
}
