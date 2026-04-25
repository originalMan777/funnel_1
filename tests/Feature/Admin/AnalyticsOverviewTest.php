<?php

namespace Tests\Feature\Admin;

use App\Models\Analytics\Conversion;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Page;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Models\User;
use App\Services\Analytics\RollupService;
use Database\Seeders\AnalyticsEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_open_the_analytics_overview(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->seed(AnalyticsEventTypeSeeder::class);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Overview')
                ->where('summary.event_types', count(config('analytics.events.default_types', [])))
                ->where('readiness.tables_ready', true)
                ->where('filters.from', now()->subDays(29)->toDateString())
                ->where('filters.to', now()->toDateString())
                ->missing('overview.top_pages')
                ->missing('overview.top_ctas')
                ->missing('overview.top_lead_boxes')
                ->missing('overview.top_popups')
                ->missing('overview.top_secondary_scenarios')
                ->missing('overview.attribution.top_last_touch_sources')
            );
    }

    public function test_overview_uses_filtered_rollup_range_for_summary_cards(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => now()->subDays(3)->toDateString(),
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_VIEWS,
            'metric_value' => 40,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => now()->subDay()->toDateString(),
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_VIEWS,
            'metric_value' => 12,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', [
                'from' => now()->subDay()->toDateString(),
                'to' => now()->subDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Overview')
                ->where('overview.summary_cards.page_views', 12)
                ->where('filters.from', now()->subDay()->toDateString())
                ->where('filters.to', now()->subDay()->toDateString())
            );
    }

    public function test_overview_exposes_event_based_time_metrics(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);

        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => '2026-04-21 09:00:00',
            'last_seen_at' => '2026-04-21 09:03:00',
        ]);

        $session = Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => '2026-04-21 09:00:00',
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
            'occurred_at' => '2026-04-21 09:00:10',
            'created_at' => '2026-04-21 09:00:10',
        ]);

        Event::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_type_id' => $eventType->id,
            'page_id' => $page->id,
            'occurred_at' => '2026-04-21 09:01:40',
            'created_at' => '2026-04-21 09:01:40',
        ]);

        Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'conversion_type_id' => 1,
            'page_id' => $page->id,
            'occurred_at' => '2026-04-21 09:02:00',
        ]);

        DailyRollup::query()->create([
            'rollup_date' => '2026-04-21',
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_VIEWS,
            'metric_value' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', [
                'from' => '2026-04-21',
                'to' => '2026-04-21',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Overview')
                ->where('overview.summary_cards.average_session_duration_seconds', 90)
                ->where('overview.summary_cards.average_time_to_conversion_seconds', 120)
            );
    }
}
