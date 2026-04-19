<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryStoreContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_category_store_returns_json_payload_for_ajax_requests(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.categories.store'), [
                'name' => 'Fresh Category',
            ], [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertCreated()
            ->assertJson([
                'category' => [
                    'name' => 'Fresh Category',
                    'slug' => 'fresh-category',
                ],
            ]);
    }

    public function test_admin_category_store_still_redirects_for_non_ajax_requests(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Browser Category',
            ])
            ->assertRedirect(route('admin.categories.index'));
    }

    public function test_admin_category_store_returns_validation_errors_for_invalid_ajax_requests(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.categories.store'), [
                'name' => '',
            ], [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_category_store_redirects_back_with_errors_for_invalid_browser_requests(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.categories.index'))
            ->post(route('admin.categories.store'), [
                'name' => '',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasErrors(['name']);
    }
}
