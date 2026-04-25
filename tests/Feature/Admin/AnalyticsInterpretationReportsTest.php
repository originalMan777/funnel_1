<?php

namespace Tests\Feature\Admin;

use App\Models\Analytics\Conversion;
use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Page;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Models\Popup;
use App\Models\User;
use App\Services\Analytics\AnalyticsScenarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsInterpretationReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_open_funnel_and_scenario_reports(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);
        $popup = Popup::factory()->create([
            'name' => 'Interpretation Popup',
            'slug' => 'interpretation-popup',
        ]);

        $session = $this->createSession($page);
        $this->createEvent($session, 'popup.impression', ['page_id' => $page->id, 'popup_id' => $popup->id]);
        $this->createEvent($session, 'popup.opened', ['page_id' => $page->id, 'popup_id' => $popup->id]);
        $this->createEvent($session, 'popup.submitted', ['page_id' => $page->id, 'popup_id' => $popup->id]);

        Conversion::query()->create([
            'session_id' => $session->id,
            'visitor_id' => $session->visitor_id,
            'conversion_type_id' => 2,
            'page_id' => $page->id,
            'popup_id' => $popup->id,
            'occurred_at' => $session->started_at,
        ]);

        app(AnalyticsScenarioService::class)->assignRange(now()->subDay(), now());

        $this->actingAs($admin)
            ->get(route('admin.analytics.funnels.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Funnels')
                ->where('report.funnels.0.label', 'Page to CTA to Lead to Conversion')
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.scenarios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Scenarios')
                ->where('report.rows.0.scenario_key', 'popup_assisted_conversion')
                ->where('report.rows.0.average_session_duration_seconds', 0)
                ->where('report.secondary_rows.0.scenario_key', 'high_engagement')
                ->where('report.sample_sessions.0.primary_scenario_key', 'popup_assisted_conversion')
                ->where('report.sample_sessions.0.secondary_scenarios.0.scenario_key', 'high_engagement')
                ->where('report.sample_sessions.0.event_based_duration_seconds', 0)
            );
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
            'started_at' => now()->subHour(),
            'entry_page_id' => $page->id,
        ]);
    }

    /**
     * @param  array<string, int|null>  $attributes
     */
    private function createEvent(Session $session, string $eventKey, array $attributes = []): Event
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
            'popup_id' => $attributes['popup_id'] ?? null,
            'occurred_at' => now()->subMinutes(10),
            'created_at' => now()->subMinutes(10),
        ]);
    }
}
