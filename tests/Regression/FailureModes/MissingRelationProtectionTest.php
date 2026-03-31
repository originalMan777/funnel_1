<?php

namespace Tests\Regression\FailureModes;

use App\Models\Lead;
use App\Models\Popup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingRelationProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_lead_relations_do_not_create_junk_lead_rows(): void
    {
        $this->post(route('leads.store'), [
            'lead_box_id' => 99999,
            'lead_slot_key' => 'home_intro',
            'first_name' => 'Missing',
            'email' => 'missing@example.com',
        ])->assertSessionHasErrors('lead_box_id');

        $this->assertSame(0, Lead::query()->count());
    }

    public function test_missing_popup_relation_does_not_create_popup_lead_rows(): void
    {
        $this->post(route('popup-leads.store'), [
            'popup_id' => 99999,
            'page_key' => 'home',
            'email' => 'ghost@example.com',
        ])->assertSessionHasErrors('popup_id');

        $this->assertDatabaseCount('popup_leads', 0);
    }

    public function test_inactive_popup_relation_never_creates_rows(): void
    {
        $popup = Popup::factory()->create(['is_active' => false]);

        $this->post(route('popup-leads.store'), [
            'popup_id' => $popup->id,
            'page_key' => 'home',
            'email' => 'inactive@example.com',
        ])->assertNotFound();

        $this->assertDatabaseCount('popup_leads', 0);
    }
}
