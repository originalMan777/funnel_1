<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeadSlotRegistryContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lead_slot_index_uses_registry_backed_labels_and_required_types(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.lead-slots.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/LeadSlots/Index')
                ->where('slots.0.key', 'home_intro')
                ->where('slots.0.label', 'Home (intro)')
                ->where('slots.0.required_type', 'resource')
                ->where('slots.3.key', 'blog_index_mid_lead')
                ->where('slots.3.label', 'Blog index (mid)')
                ->where('slots.3.required_type', 'offer')
                ->where('slots.8.key', 'blog_post_before_related')
                ->where('slots.8.label', 'Blog post before related')
                ->where('slots.8.required_type', 'offer')
            );
    }
}
