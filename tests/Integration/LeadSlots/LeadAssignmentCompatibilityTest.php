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

    public function test_home_intro_only_resolves_resource_assignments(): void
    {
        $slot = LeadSlot::factory()->create(['key' => 'home_intro', 'is_enabled' => true]);
        $resource = LeadBox::factory()->resource()->active()->create();
        $service = LeadBox::factory()->service()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $service->id,
        ]);

        $this->assertNull(app(LeadSlotResolver::class)->resolve('home')['home_intro']);

        LeadAssignment::query()->where('lead_slot_id', $slot->id)->delete();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $resource->id,
        ]);

        $this->assertSame(
            $resource->id,
            app(LeadSlotResolver::class)->resolve('home')['home_intro']['leadBoxId']
        );
    }

    public function test_home_mid_only_resolves_service_assignments(): void
    {
        $slot = LeadSlot::factory()->create(['key' => 'home_mid', 'is_enabled' => true]);
        $service = LeadBox::factory()->service()->active()->create();
        $offer = LeadBox::factory()->offer()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $offer->id,
        ]);

        $this->assertNull(app(LeadSlotResolver::class)->resolve('home')['home_mid']);

        LeadAssignment::query()->where('lead_slot_id', $slot->id)->delete();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $service->id,
        ]);

        $this->assertSame(
            $service->id,
            app(LeadSlotResolver::class)->resolve('home')['home_mid']['leadBoxId']
        );
    }
}
