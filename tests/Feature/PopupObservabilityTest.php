<?php

namespace Tests\Feature;

use App\Models\Analytics\Conversion;
use App\Models\Analytics\Event;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Models\Popup;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\Communications\FakeMarketingProvider;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\TestCase;

class PopupObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_popup_submission_projects_analytics_events_and_conversion(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);

        $popup = Popup::factory()->create([
            'slug' => 'welcome-popup',
            'target_pages' => ['home'],
            'form_fields' => ['name', 'email'],
            'suppression_scope' => 'all_lead_popups',
        ]);

        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $session = Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => now(),
        ]);

        $this->from(route('home'))
            ->withHeader('referer', route('home'))
            ->withCookie(config('analytics.cookies.visitor'), $visitor->visitor_key)
            ->withCookie(config('analytics.cookies.session'), $session->session_key)
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'name' => 'Jameel',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('analytics_events', [
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'popup_id' => $popup->id,
            'subject_type' => 'App\\Models\\PopupLead',
        ]);

        $this->assertDatabaseHas('analytics_conversions', [
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'popup_id' => $popup->id,
            'popup_lead_id' => 1,
        ]);

        $this->assertTrue(
            Event::query()->whereHas('eventType', fn ($query) => $query->where('event_key', 'popup.submitted'))->exists()
        );

        $this->assertTrue(
            Event::query()->whereHas('eventType', fn ($query) => $query->where('event_key', 'conversion.recorded'))->exists()
        );

        $this->assertSame(1, Conversion::query()->count());
    }
}
