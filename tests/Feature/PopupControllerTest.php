<?php

namespace Tests\Feature;

use App\Models\Popup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_popup(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.popups.store'), $this->validPayload([
                'name' => 'Create Popup',
                'slug' => 'create-popup',
            ]))
            ->assertRedirect(route('admin.popups.index'))
            ->assertSessionHas('success', 'Popup created successfully.');

        $this->assertDatabaseHas('popups', [
            'name' => 'Create Popup',
            'slug' => 'create-popup',
            'is_active' => 1,
            'audience' => 'guests',
        ]);
    }

    public function test_update_popup(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $popup = Popup::factory()->create([
            'name' => 'Before Popup',
            'slug' => 'before-popup',
            'post_submit_action' => 'redirect',
            'post_submit_redirect_url' => 'https://example.com/before',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.popups.update', $popup), $this->validPayload([
                'name' => 'Updated Popup',
                'slug' => 'updated-popup',
                'headline' => 'Updated headline',
                'post_submit_action' => 'message',
            ]))
            ->assertRedirect(route('admin.popups.index'))
            ->assertSessionHas('success', 'Popup updated successfully.');

        $this->assertDatabaseHas('popups', [
            'id' => $popup->id,
            'name' => 'Updated Popup',
            'slug' => 'updated-popup',
            'headline' => 'Updated headline',
            'post_submit_action' => 'message',
            'post_submit_redirect_url' => null,
        ]);
    }

    public function test_slug_auto_generation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.popups.store'), $this->validPayload([
                'name' => 'Auto Slug Popup',
                'slug' => '',
            ]))
            ->assertRedirect(route('admin.popups.index'));

        $this->assertDatabaseHas('popups', [
            'name' => 'Auto Slug Popup',
            'slug' => 'auto-slug-popup',
        ]);
    }

    public function test_invalid_validation_rejection(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $initialCount = Popup::query()->count();

        $this->actingAs($admin)
            ->from(route('admin.popups.create'))
            ->post(route('admin.popups.store'), $this->validPayload([
                'name' => '',
                'priority' => 0,
                'audience' => 'invalid-audience',
            ]))
            ->assertRedirect(route('admin.popups.create'))
            ->assertSessionHasErrors(['name', 'priority', 'audience']);

        $this->assertSame($initialCount, Popup::query()->count());
    }

    public function test_unauthorized_user_blocked_with_403(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $popup = Popup::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.popups.store'), $this->validPayload())
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.popups.update', $popup), $this->validPayload(['name' => 'Blocked']))
            ->assertForbidden();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Enterprise Popup',
            'slug' => 'enterprise-popup',
            'type' => 'general',
            'role' => 'standard',
            'priority' => 10,
            'is_active' => '1',
            'eyebrow' => 'New',
            'headline' => 'Capture more leads',
            'body' => 'Popup body copy',
            'cta_text' => 'Submit',
            'success_message' => 'Thanks. We received your information.',
            'layout' => 'centered',
            'trigger_type' => 'time',
            'trigger_delay' => 2,
            'trigger_scroll' => null,
            'target_pages' => ['home', 'contact'],
            'device' => 'all',
            'frequency' => 'once_day',
            'audience' => 'guests',
            'suppress_if_lead_captured' => '1',
            'suppression_scope' => 'all_lead_popups',
            'form_fields' => ['name', 'email'],
            'lead_type' => 'general',
            'post_submit_action' => 'redirect',
            'post_submit_redirect_url' => 'https://example.com/next',
        ], $overrides);
    }
}
