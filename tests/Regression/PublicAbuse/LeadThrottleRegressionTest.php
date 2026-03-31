<?php

namespace Tests\Regression\PublicAbuse;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadThrottleRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_endpoint_throttles_after_ten_requests_per_minute(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->post(route('contact.store'), [
                'name' => "User {$attempt}",
                'email' => "user{$attempt}@example.com",
                'message' => 'Throttle test message',
            ])->assertRedirect();
        }

        $this->post(route('contact.store'), [
            'name' => 'User 11',
            'email' => 'user11@example.com',
            'message' => 'Throttle test message',
        ])->assertTooManyRequests();

        $this->assertDatabaseCount('leads', 10);
    }
}
