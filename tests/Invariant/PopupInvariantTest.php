<?php

namespace Tests\Invariant;

use App\Models\Popup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PopupInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_eligible_popups_are_shared_for_the_current_request_context(): void
    {
        Popup::factory()->create([
            'slug' => 'guest-home-popup',
            'target_pages' => ['home'],
            'audience' => 'guests',
        ]);

        Popup::factory()->create([
            'slug' => 'auth-home-popup',
            'target_pages' => ['home'],
            'audience' => 'authenticated',
        ]);

        Popup::factory()->create([
            'slug' => 'contact-popup',
            'target_pages' => ['contact'],
            'audience' => 'guests',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('popupManager.pageKey', 'home')
                ->where('popupManager.popups', function ($popups): bool {
                    $slugs = collect($popups)->pluck('slug');

                    return $slugs->contains('guest-home-popup')
                        && ! $slugs->contains('auth-home-popup')
                        && ! $slugs->contains('contact-popup');
                })
            );
    }

    public function test_strategy_routes_resolve_their_shared_page_keys_from_actual_route_names(): void
    {
        Popup::factory()->create([
            'slug' => 'buyers-popup',
            'target_pages' => ['buyers'],
            'audience' => 'guests',
        ]);

        Popup::factory()->create([
            'slug' => 'sellers-popup',
            'target_pages' => ['sellers'],
            'audience' => 'guests',
        ]);

        $this->get(route('buyers.strategy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BuyersStrategy')
                ->where('popupManager.pageKey', 'buyers')
                ->where('popupManager.popups', function ($popups): bool {
                    $slugs = collect($popups)->pluck('slug');

                    return $slugs->contains('buyers-popup')
                        && ! $slugs->contains('sellers-popup');
                })
            );

        $this->get(route('sellers.strategy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SellersStrategy')
                ->where('popupManager.pageKey', 'sellers')
                ->where('popupManager.popups', function ($popups): bool {
                    $slugs = collect($popups)->pluck('slug');

                    return $slugs->contains('sellers-popup')
                        && ! $slugs->contains('buyers-popup');
                })
            );
    }

    public function test_global_everyone_popups_remain_shared_without_page_targeting(): void
    {
        Popup::factory()->create([
            'slug' => 'global-popup',
            'target_pages' => [],
            'audience' => 'everyone',
        ]);

        Popup::factory()->create([
            'slug' => 'auth-only-popup',
            'target_pages' => [],
            'audience' => 'authenticated',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('popupManager.pageKey', 'home')
                ->where('popupManager.popups', function ($popups): bool {
                    $slugs = collect($popups)->pluck('slug');

                    return $slugs->contains('global-popup')
                        && ! $slugs->contains('auth-only-popup');
                })
            );
    }

    public function test_authenticated_requests_only_receive_matching_global_and_page_targeted_popups(): void
    {
        Popup::factory()->create([
            'slug' => 'global-everyone-popup',
            'target_pages' => [],
            'audience' => 'everyone',
        ]);

        Popup::factory()->create([
            'slug' => 'global-auth-popup',
            'target_pages' => [],
            'audience' => 'authenticated',
        ]);

        Popup::factory()->create([
            'slug' => 'home-auth-popup',
            'target_pages' => ['home'],
            'audience' => 'authenticated',
        ]);

        Popup::factory()->create([
            'slug' => 'home-guest-popup',
            'target_pages' => ['home'],
            'audience' => 'guests',
        ]);

        Popup::factory()->create([
            'slug' => 'contact-auth-popup',
            'target_pages' => ['contact'],
            'audience' => 'authenticated',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('popupManager.pageKey', 'home')
                ->where('popupManager.isAuthenticated', true)
                ->where('popupManager.popups', function ($popups): bool {
                    $slugs = collect($popups)->pluck('slug');

                    return $slugs->contains('global-everyone-popup')
                        && $slugs->contains('global-auth-popup')
                        && $slugs->contains('home-auth-popup')
                        && ! $slugs->contains('home-guest-popup')
                        && ! $slugs->contains('contact-auth-popup');
                })
            );
    }

    public function test_ineligible_popups_cannot_be_submitted_directly(): void
    {
        $authenticatedOnlyPopup = Popup::factory()->create([
            'slug' => 'members-only',
            'audience' => 'authenticated',
            'target_pages' => ['home'],
            'form_fields' => ['email'],
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $authenticatedOnlyPopup->id,
                'page_key' => 'home',
                'email' => 'guest@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 0);
    }

    public function test_lead_captured_suppression_remains_effective_for_all_lead_popups(): void
    {
        $popup = Popup::factory()->create([
            'slug' => 'suppressed-popup',
            'target_pages' => ['home'],
            'suppression_scope' => 'all_lead_popups',
            'suppress_if_lead_captured' => true,
            'form_fields' => ['email'],
        ]);

        $this->withCookie('nojo_lead_captured', '1')
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('popupManager.leadCaptured', true)
                ->has('popupManager.popups', 0)
            );

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withCookie('nojo_lead_captured', '1')
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'member@example.com',
            ])
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 0);
    }
}
