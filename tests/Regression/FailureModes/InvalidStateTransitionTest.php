<?php

namespace Tests\Regression\FailureModes;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvalidStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_cannot_transition_post_to_published_or_deleted_state(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $post = Post::factory()->create();

        $this->actingAs($user)->post(route('admin.posts.publish', $post))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.posts.destroy', $post))->assertForbidden();

        $post->refresh();
        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertNull($post->published_at);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_cross_type_lead_slot_reassignment_replaces_existing_assignment(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $slot = LeadSlot::factory()->create(['key' => 'home_intro', 'is_enabled' => true]);
        $validBox = LeadBox::factory()->resource()->active()->create();
        $replacementBox = LeadBox::factory()->service()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $validBox->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.lead-slots.index'))
            ->put(route('admin.lead-slots.update', $slot), [
                'is_enabled' => true,
                'lead_box_id' => $replacementBox->id,
            ])
            ->assertRedirect(route('admin.lead-slots.index'));

        $this->assertDatabaseMissing('lead_assignments', [
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $validBox->id,
        ]);
        $this->assertDatabaseHas('lead_assignments', [
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $replacementBox->id,
        ]);
    }
}
