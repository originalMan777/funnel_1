<?php

namespace Tests\Feature\Admin;

use App\Models\Acquisition;
use App\Models\AcquisitionPath;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Service;
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

    public function test_admin_can_assign_any_active_lead_box_type_to_a_slot(): void
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
            ->assertRedirect(route('admin.lead-slots.index'));

        $this->assertDatabaseHas('lead_assignments', [
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

    public function test_admin_can_assign_acquisition_context_to_a_slot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $slot = LeadSlot::factory()->create(['key' => 'home_mid', 'is_enabled' => true]);
        $box = LeadBox::factory()->service()->active()->create();
        $acquisition = Acquisition::query()->create([
            'name' => 'Buyer Acquisition',
            'slug' => 'buyer-acquisition',
            'is_active' => true,
        ]);
        $service = Service::query()->create([
            'acquisition_id' => $acquisition->id,
            'name' => 'Buyer Consultation',
            'slug' => 'buyer-consultation',
            'is_active' => true,
        ]);
        $path = AcquisitionPath::query()->create([
            'acquisition_id' => $acquisition->id,
            'service_id' => $service->id,
            'name' => 'Buyer Consultation Blog Inline',
            'slug' => 'buyer-consultation-blog-inline',
            'path_key' => 'buyer.consultation.blog-inline',
            'entry_type' => 'lead_slot',
            'source_context' => 'blog_inline',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.lead-slots.index'))
            ->put(route('admin.lead-slots.update', $slot), [
                'is_enabled' => true,
                'lead_box_id' => $box->id,
                'acquisition_id' => $acquisition->id,
                'service_id' => $service->id,
                'acquisition_path_id' => $path->id,
            ])
            ->assertRedirect(route('admin.lead-slots.index'));

        $this->assertDatabaseHas('lead_assignments', [
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
            'acquisition_id' => $acquisition->id,
            'service_id' => $service->id,
            'acquisition_path_id' => $path->id,
            'acquisition_path_key' => $path->path_key,
        ]);
    }

    public function test_admin_saves_assignment_and_discards_stale_service_context(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $slot = LeadSlot::factory()->create(['key' => 'home_mid', 'is_enabled' => true]);
        $box = LeadBox::factory()->service()->active()->create();
        $buyerAcquisition = Acquisition::query()->create([
            'name' => 'Buyer Acquisition',
            'slug' => 'buyer-acquisition',
            'is_active' => true,
        ]);
        $sellerAcquisition = Acquisition::query()->create([
            'name' => 'Seller Acquisition',
            'slug' => 'seller-acquisition',
            'is_active' => true,
        ]);
        $sellerService = Service::query()->create([
            'acquisition_id' => $sellerAcquisition->id,
            'name' => 'Listing Consultation',
            'slug' => 'listing-consultation',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.lead-slots.index'))
            ->put(route('admin.lead-slots.update', $slot), [
                'is_enabled' => true,
                'lead_box_id' => $box->id,
                'acquisition_id' => $buyerAcquisition->id,
                'service_id' => $sellerService->id,
            ])
            ->assertRedirect(route('admin.lead-slots.index'));

        $this->assertDatabaseHas('lead_assignments', [
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
            'acquisition_id' => $buyerAcquisition->id,
            'service_id' => null,
            'acquisition_path_id' => null,
        ]);
    }

    public function test_admin_saves_assignment_and_discards_context_when_acquisition_is_missing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $slot = LeadSlot::factory()->create(['key' => 'home_mid', 'is_enabled' => true]);
        $box = LeadBox::factory()->service()->active()->create();
        $acquisition = Acquisition::query()->create([
            'name' => 'Buyer Acquisition',
            'slug' => 'buyer-acquisition',
            'is_active' => true,
        ]);
        $service = Service::query()->create([
            'acquisition_id' => $acquisition->id,
            'name' => 'Buyer Consultation',
            'slug' => 'buyer-consultation',
            'is_active' => true,
        ]);
        $path = AcquisitionPath::query()->create([
            'acquisition_id' => $acquisition->id,
            'service_id' => $service->id,
            'name' => 'Buyer Consultation Blog Inline',
            'slug' => 'buyer-consultation-blog-inline',
            'path_key' => 'buyer.consultation.blog-inline',
            'entry_type' => 'lead_slot',
            'source_context' => 'blog_inline',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.lead-slots.index'))
            ->put(route('admin.lead-slots.update', $slot), [
                'is_enabled' => true,
                'lead_box_id' => $box->id,
                'service_id' => $service->id,
                'acquisition_path_id' => $path->id,
            ])
            ->assertRedirect(route('admin.lead-slots.index'));

        $this->assertDatabaseHas('lead_assignments', [
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
            'acquisition_id' => null,
            'service_id' => null,
            'acquisition_path_id' => null,
        ]);
    }
}
