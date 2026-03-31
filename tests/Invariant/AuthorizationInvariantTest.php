<?php

namespace Tests\Invariant;

use App\Models\LeadSlot;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_never_mutate_protected_admin_resources(): void
    {
        $leadSlot = LeadSlot::factory()->create(['key' => 'home_intro']);
        $post = Post::factory()->create();

        $this->post(route('admin.posts.store'), $this->validPostPayload())
            ->assertRedirect(route('login'));

        $this->post(route('admin.media.store'), [
            'folder' => 'blog',
        ])->assertRedirect(route('login'));

        $this->post(route('admin.popups.store'), $this->validPopupPayload())
            ->assertRedirect(route('login'));

        $this->post(route('admin.lead-boxes.resource.store'), $this->validResourceLeadBoxPayload())
            ->assertRedirect(route('login'));

        $this->put(route('admin.lead-slots.update', $leadSlot), [
            'is_enabled' => true,
            'lead_box_id' => null,
        ])->assertRedirect(route('login'));

        $this->post(route('admin.posts.publish', $post))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_never_mutate_protected_admin_resources(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $post = Post::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.posts.store'), $this->validPostPayload())
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.posts.publish', $post))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.posts.destroy', $post))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.media.store'), ['folder' => 'blog'])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.popups.store'), $this->validPopupPayload())
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.lead-boxes.resource.store'), $this->validResourceLeadBoxPayload())
            ->assertForbidden();
    }

    public function test_admin_users_retain_mutation_access_for_core_protected_resources(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), $this->validPostPayload())
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.popups.store'), $this->validPopupPayload())
            ->assertRedirect(route('admin.popups.index'));

        $this->actingAs($admin)
            ->post(route('admin.lead-boxes.resource.store'), $this->validResourceLeadBoxPayload())
            ->assertRedirect();
    }

    private function validPostPayload(): array
    {
        return [
            'title' => 'Invariant Post',
            'content' => '<p>Body</p>',
            'sources' => 'https://example.com/source',
            'featured_image_path' => '/images/blog/invariant.png',
        ];
    }

    private function validPopupPayload(): array
    {
        return [
            'name' => 'Invariant Popup',
            'type' => 'general',
            'role' => 'standard',
            'priority' => 10,
            'headline' => 'Invariant popup',
            'cta_text' => 'Submit',
            'layout' => 'centered',
            'trigger_type' => 'time',
            'device' => 'all',
            'frequency' => 'once_day',
            'audience' => 'guests',
            'suppression_scope' => 'all_lead_popups',
            'lead_type' => 'general',
            'post_submit_action' => 'message',
        ];
    }

    private function validResourceLeadBoxPayload(): array
    {
        return [
            'status' => 'active',
            'internal_name' => 'Invariant Resource Box',
            'title' => 'Resource Box',
            'short_text' => 'Helpful text.',
            'button_text' => 'Download',
            'icon_key' => 'book-open',
            'visual_preset' => 'default',
        ];
    }
}
