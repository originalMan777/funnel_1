<?php

namespace Tests\Invariant;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Services\LeadSlots\LeadSlotResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadSlotInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_incompatible_assignments_do_not_resolve_as_usable_slots(): void
    {
        $slot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);

        $serviceBox = LeadBox::factory()->service()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $serviceBox->id,
        ]);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertNull($resolved['home_intro']);
    }

    public function test_disabled_and_inactive_states_do_not_leak_through_resolution(): void
    {
        $disabledSlot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => false,
        ]);

        $inactiveBox = LeadBox::factory()->service()->create([
            'status' => LeadBox::STATUS_INACTIVE,
        ]);

        $enabledSlot = LeadSlot::factory()->create([
            'key' => 'home_mid',
            'is_enabled' => true,
        ]);

        $resourceBox = LeadBox::factory()->resource()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $disabledSlot->id,
            'lead_box_id' => $resourceBox->id,
        ]);

        LeadAssignment::factory()->create([
            'lead_slot_id' => $enabledSlot->id,
            'lead_box_id' => $inactiveBox->id,
        ]);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertNull($resolved['home_intro']);
        $this->assertNull($resolved['home_mid']);
    }
}
