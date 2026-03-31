<?php

namespace Tests\Regression\PublicAbuse;

use App\Models\Popup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopupReplayRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_popup_cookie_replay_is_rejected_without_creating_duplicate_rows(): void
    {
        $popup = Popup::factory()->create([
            'slug' => 'replay-guard',
            'form_fields' => ['email'],
        ]);

        $this->from(route('home'))
            ->withCookie('nojo_popup_submitted_replay_guard', '1')
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'blocked@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 0);
    }

    public function test_recent_email_replay_for_the_same_popup_is_rejected(): void
    {
        $popup = Popup::factory()->create([
            'slug' => 'email-replay',
            'form_fields' => ['email'],
        ]);

        $this->post(route('popup-leads.store'), [
            'popup_id' => $popup->id,
            'page_key' => 'home',
            'email' => 'same@example.com',
        ])->assertRedirect();

        $this->post(route('popup-leads.store'), [
            'popup_id' => $popup->id,
            'page_key' => 'home',
            'email' => 'same@example.com',
        ])->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 1);
    }
}
