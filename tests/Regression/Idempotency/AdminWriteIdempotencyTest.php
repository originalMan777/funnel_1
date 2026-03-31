<?php

namespace Tests\Regression\Idempotency;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWriteIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeating_the_same_lead_slot_assignment_does_not_duplicate_assignment_rows(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $slot = LeadSlot::factory()->create(['key' => 'home_intro', 'is_enabled' => true]);
        $box = LeadBox::factory()->resource()->active()->create();

        $payload = [
            'is_enabled' => true,
            'lead_box_id' => $box->id,
        ];

        $this->actingAs($admin)->put(route('admin.lead-slots.update', $slot), $payload)->assertRedirect();
        $this->actingAs($admin)->put(route('admin.lead-slots.update', $slot), $payload)->assertRedirect();

        $this->assertSame(
            1,
            LeadAssignment::query()->where('lead_slot_id', $slot->id)->count()
        );
    }

    public function test_repeating_publish_and_unpublish_actions_keeps_post_state_valid(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->create();

        $this->actingAs($admin)->post(route('admin.posts.publish', $post))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.posts.publish', $post))->assertRedirect();

        $post->refresh();
        $this->assertSame(Post::STATUS_PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at);

        $this->actingAs($admin)->post(route('admin.posts.unpublish', $post))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.posts.unpublish', $post))->assertRedirect();

        $post->refresh();
        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertNull($post->published_at);
    }
}
