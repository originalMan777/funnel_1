<?php

namespace Tests\Feature;

use App\Models\Analytics\Event;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsIngestTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_records_page_and_cta_events_with_catalog_rows(): void
    {
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

        $this->postJson(route('analytics.ingest'), [
            'visitor_key' => $visitor->visitor_key,
            'session_key' => $session->session_key,
            'events' => [
                [
                    'event_key' => 'page.view',
                    'page_key' => 'home',
                    'surface_key' => 'home.hero',
                ],
                [
                    'event_key' => 'cta.click',
                    'page_key' => 'home',
                    'cta_key' => 'home.hero.consultation',
                    'surface_key' => 'home.hero',
                    'properties' => [
                        'source' => 'test',
                    ],
                ],
            ],
        ])->assertAccepted();

        $this->assertDatabaseHas('analytics_pages', [
            'page_key' => 'home',
        ]);

        $this->assertDatabaseHas('analytics_ctas', [
            'cta_key' => 'home.hero.consultation',
        ]);

        $this->assertDatabaseHas('analytics_surfaces', [
            'surface_key' => 'home.hero',
        ]);

        $this->assertSame(2, Event::query()->count());
    }
}
