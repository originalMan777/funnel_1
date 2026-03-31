<?php

namespace Tests\Feature;

use App\Models\LeadBox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadBoxTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_resource_lead_box(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.lead-boxes.resource.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('lead_boxes', [
            'type' => LeadBox::TYPE_RESOURCE,
            'status' => LeadBox::STATUS_ACTIVE,
            'internal_name' => 'Resource Box One',
            'title' => 'Get the guide',
        ]);
    }

    public function test_update_resource_lead_box(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $leadBox = LeadBox::factory()->resource()->create([
            'status' => LeadBox::STATUS_DRAFT,
            'internal_name' => 'Before Box',
            'title' => 'Before Title',
            'content' => ['visual_preset' => 'default'],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.lead-boxes.resource.update', $leadBox), $this->validPayload([
                'internal_name' => 'After Box',
                'title' => 'After Title',
                'visual_preset' => 'default',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('lead_boxes', [
            'id' => $leadBox->id,
            'internal_name' => 'After Box',
            'title' => 'After Title',
        ]);

        $this->assertSame('default', $leadBox->fresh()->content['visual_preset']);
    }

    public function test_invalid_type_blocked(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $serviceBox = LeadBox::factory()->service()->create();

        $this->actingAs($admin)
            ->put(route('admin.lead-boxes.resource.update', $serviceBox), $this->validPayload())
            ->assertNotFound();
    }

    public function test_validation_errors(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.lead-boxes.create'))
            ->post(route('admin.lead-boxes.resource.store'), [
                'status' => 'invalid-status',
                'internal_name' => '',
                'title' => '',
                'visual_preset' => 'invalid-preset',
            ])
            ->assertRedirect(route('admin.lead-boxes.create'))
            ->assertSessionHasErrors(['status', 'internal_name', 'title', 'visual_preset']);

        $this->assertDatabaseCount('lead_boxes', 0);
    }

    public function test_unauthorized_user_blocked(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $leadBox = LeadBox::factory()->resource()->create();

        $this->actingAs($user)
            ->post(route('admin.lead-boxes.resource.store'), $this->validPayload())
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.lead-boxes.resource.update', $leadBox), $this->validPayload())
            ->assertForbidden();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'status' => LeadBox::STATUS_ACTIVE,
            'internal_name' => 'Resource Box One',
            'title' => 'Get the guide',
            'short_text' => 'Helpful short text',
            'button_text' => 'Download',
            'icon_key' => 'book-open',
            'visual_preset' => 'default',
        ], $overrides);
    }
}
