<?php

namespace Tests\Regression\Idempotency;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadSubmissionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_lead_submissions_keep_cookie_state_stable_and_persist_complete_records(): void
    {
        $slot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);

        $box = LeadBox::factory()->resource()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
        ]);

        $payload = [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'page_key' => 'home',
            'source_url' => 'https://example.com/home',
            'first_name' => 'Repeat',
            'email' => 'repeat@example.com',
        ];

        $this->from(route('home'))
            ->post(route('leads.store'), $payload)
            ->assertRedirect(route('home'))
            ->assertCookie('nojo_lead_captured', '1');

        $this->from(route('home'))
            ->withCookie('nojo_lead_captured', '1')
            ->post(route('leads.store'), $payload)
            ->assertRedirect(route('home'))
            ->assertCookie('nojo_lead_captured', '1');

        $this->assertDatabaseCount('leads', 2);
        $this->assertDatabaseHas('leads', [
            'lead_slot_key' => 'home_intro',
            'email' => 'repeat@example.com',
            'type' => LeadBox::TYPE_RESOURCE,
        ]);
    }
}
