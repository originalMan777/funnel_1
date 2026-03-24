<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_hub_shows_user_links_for_regular_users_and_hides_admin_links(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Profile')
                ->where('auth.user.is_admin', false)
                ->where('auth.user.email', $user->email)
            );
    }

    public function test_profile_hub_shows_admin_links_for_admins(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Profile')
                ->where('auth.user.is_admin', true)
                ->where('auth.user.email', $admin->email)
            );
    }

    public function test_profile_shortcut_redirects_to_the_real_settings_profile_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertRedirect(route('profile.edit', absolute: false));
    }
}
