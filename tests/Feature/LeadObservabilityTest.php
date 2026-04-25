<?php

namespace Tests\Feature;

use App\Models\Analytics\Conversion;
use App\Models\Analytics\Event;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\Communications\FakeMarketingProvider;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\TestCase;

class LeadObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_lead_submission_projects_analytics_events_and_conversion(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        $slot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);
        $box = LeadBox::factory()->resource()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
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

        $this->withCookie(config('analytics.cookies.visitor'), $visitor->visitor_key)
            ->withCookie(config('analytics.cookies.session'), $session->session_key)
            ->post(route('leads.store'), [
                'lead_box_id' => $box->id,
                'lead_slot_key' => $slot->key,
                'page_key' => 'home',
                'source_url' => 'https://example.com/home',
                'first_name' => 'Jameel',
                'email' => 'lead@example.com',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('analytics_events', [
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'lead_box_id' => $box->id,
            'subject_type' => 'App\\Models\\Lead',
        ]);

        $this->assertDatabaseHas('analytics_conversions', [
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'lead_box_id' => $box->id,
            'lead_id' => 1,
        ]);

        $this->assertTrue(
            Event::query()->whereHas('eventType', fn ($query) => $query->where('event_key', 'lead_form.submitted'))->exists()
        );

        $this->assertTrue(
            Event::query()->whereHas('eventType', fn ($query) => $query->where('event_key', 'conversion.recorded'))->exists()
        );

        $this->assertSame(1, Conversion::query()->count());
    }
}
