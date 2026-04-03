<?php

namespace Tests\Feature\Admin;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadSlotAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_an_active_matching_lead_box_to_a_slot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $slot = LeadSlot::factory()->create(['key' => 'home_intro', 'is_enabled' => true]);
        $box = LeadBox::factory()->resource()->active()->create();

        $this->actingAs($admin)
            ->from(route('admin.lead-slots.index'))
            ->put(route('admin.lead-slots.update', $slot), [
                'is_enabled' => true,
                'lead_box_id' => $box->id,
            ])
            ->assertRedirect(route('admin.lead-slots.index'));

        $this->assertDatabaseHas('lead_assignments', [
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
        ]);
    }

    public function test_admin_cannot_assign_wrong_lead_box_type_to_a_slot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $slot = LeadSlot::factory()->create(['key' => 'home_intro', 'is_enabled' => true]);
        $box = LeadBox::factory()->service()->active()->create();

        $this->actingAs($admin)
            ->from(route('admin.lead-slots.index'))
            ->put(route('admin.lead-slots.update', $slot), [
                'is_enabled' => true,
                'lead_box_id' => $box->id,
            ])
            ->assertRedirect(route('admin.lead-slots.index'))
            ->assertSessionHasErrors('lead_box_id');
            

        $this->assertDatabaseMissing('lead_assignments', [
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
        ]);
    }

    public function test_admin_cannot_assign_inactive_lead_box_to_a_slot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $slot = LeadSlot::factory()->create(['key' => 'home_mid', 'is_enabled' => true]);
        $box = LeadBox::factory()->service()->create([
            'status' => LeadBox::STATUS_INACTIVE,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.lead-slots.index'))
            ->put(route('admin.lead-slots.update', $slot), [
                'is_enabled' => true,
                'lead_box_id' => $box->id,
            ])
            ->assertRedirect(route('admin.lead-slots.index'))
            ->assertSessionHasErrors('lead_box_id');

        $this->assertNull(LeadAssignment::query()->where('lead_slot_id', $slot->id)->first());
    }
}
