<?php

namespace Tests\Regression\Idempotency;

use App\Models\Popup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopupSubmissionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_popup_cookie_blocks_duplicate_submissions_for_the_same_popup(): void
    {
        $popup = Popup::factory()->create([
            'slug' => 'duplicate-guard',
            'form_fields' => ['email'],
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertCookie('nojo_popup_submitted_duplicate_guard', '1');

        $this->from(route('home'))
            ->withCookie('nojo_popup_submitted_duplicate_guard', '1')
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 1);
    }

    public function test_lead_capture_cookie_makes_all_lead_popup_submission_effectively_idempotent(): void
    {
        $first = Popup::factory()->create([
            'slug' => 'first-popup',
            'form_fields' => ['email'],
            'suppression_scope' => 'all_lead_popups',
        ]);

        $second = Popup::factory()->create([
            'slug' => 'second-popup',
            'form_fields' => ['email'],
            'suppression_scope' => 'all_lead_popups',
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $first->id,
                'page_key' => 'home',
                'email' => 'first@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertCookie('nojo_lead_captured', '1');

        $this->from(route('home'))
            ->withCookie('nojo_lead_captured', '1')
            ->post(route('popup-leads.store'), [
                'popup_id' => $second->id,
                'page_key' => 'home',
                'email' => 'second@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 1);
    }
}
