<?php

namespace Tests\Integration\LeadSlots;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Services\LeadSlots\LeadSlotResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadAssignmentCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_intro_resolves_any_active_assigned_lead_box_type(): void
    {
        $slot = LeadSlot::factory()->create(['key' => 'home_intro', 'is_enabled' => true]);
        $service = LeadBox::factory()->service()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $service->id,
        ]);

        $this->assertSame(
            $service->id,
            app(LeadSlotResolver::class)->resolve('home')['home_intro']['leadBoxId']
        );
    }

    public function test_home_mid_resolves_any_active_assigned_lead_box_type(): void
    {
        $slot = LeadSlot::factory()->create(['key' => 'home_mid', 'is_enabled' => true]);
        $offer = LeadBox::factory()->offer()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $offer->id,
        ]);

        $this->assertSame(
            $offer->id,
            app(LeadSlotResolver::class)->resolve('home')['home_mid']['leadBoxId']
        );
    }
}
