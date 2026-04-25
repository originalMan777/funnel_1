<?php

namespace Tests\Feature;

use Database\Seeders\AnalyticsEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_queue_analytics_identity_cookies(): void
    {
        $this->seed(AnalyticsEventTypeSeeder::class);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertCookie(config('analytics.cookies.visitor'));
        $response->assertCookie(config('analytics.cookies.session'));
    }
}
