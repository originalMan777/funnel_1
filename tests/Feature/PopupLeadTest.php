<?php

namespace Tests\Feature;

use App\Models\Popup;
use App\Models\PopupLead;
use App\Models\User;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PopupLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_submission(): void
    {
        $popup = Popup::factory()->create([
            'slug' => 'welcome-popup',
            'target_pages' => ['home'],
            'form_fields' => ['name', 'email', 'phone'],
            'suppression_scope' => 'all_lead_popups',
        ]);

        $this->from(route('home'))
            ->withHeader('referer', route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'name' => 'Jameel',
                'email' => 'popup@example.com',
                'phone' => '555-1212',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('popupLeadSuccess', $popup->success_message)
            ->assertCookie('nojo_popup_submitted_welcome_popup', '1')
            ->assertCookie('nojo_lead_captured', '1');

        $this->assertDatabaseHas('popup_leads', [
            'popup_id' => $popup->id,
            'page_key' => 'home',
            'name' => 'Jameel',
            'email' => 'popup@example.com',
            'phone' => '555-1212',
            'lead_type' => $popup->lead_type,
            'source_url' => route('home'),
        ]);
    }

    public function test_it_rejects_honeypot_spam(): void
    {
        $popup = Popup::factory()->create([
            'target_pages' => ['home'],
            'form_fields' => ['email'],
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'spam@example.com',
                'website' => 'x',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 0);
    }

    public function test_inactive_popup_rejection(): void
    {
        $popup = Popup::factory()->create([
            'is_active' => false,
            'form_fields' => ['email'],
        ]);

        $this->post(route('popup-leads.store'), [
            'popup_id' => $popup->id,
            'page_key' => 'home',
            'email' => 'popup@example.com',
        ])->assertNotFound();

        $this->assertDatabaseCount('popup_leads', 0);
    }

    public function test_audience_mismatch_rejection(): void
    {
        $popup = Popup::factory()->create([
            'audience' => 'authenticated',
            'target_pages' => ['home'],
            'form_fields' => ['email'],
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'guest@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 0);
    }

    public function test_invalid_page_key_rejection(): void
    {
        $popup = Popup::factory()->create([
            'target_pages' => ['contact'],
            'form_fields' => ['email'],
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 0);
    }

    public function test_it_blocks_duplicate_popup_submission_by_cookie(): void
    {
        $popup = Popup::factory()->create([
            'slug' => 'cookie-guard',
            'target_pages' => ['home'],
            'form_fields' => ['email'],
            'suppression_scope' => 'this_popup_only',
        ]);

        $this->from(route('home'))
            ->withCookie('nojo_popup_submitted_cookie_guard', '1')
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 0);
    }

    public function test_ip_rate_limiting_two_minute_window(): void
    {
        $popup = Popup::factory()->create([
            'target_pages' => ['home'],
            'form_fields' => ['email'],
        ]);

        PopupLead::create([
            'popup_id' => $popup->id,
            'page_key' => 'home',
            'source_url' => route('home'),
            'lead_type' => $popup->lead_type,
            'email' => 'first@example.com',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'metadata' => [],
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'second@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertSame(1, PopupLead::query()->count());
    }

    public function test_email_rate_limiting_ten_minute_window(): void
    {
        $popup = Popup::factory()->create([
            'target_pages' => ['home'],
            'form_fields' => ['email'],
        ]);

        PopupLead::create([
            'popup_id' => $popup->id,
            'page_key' => 'home',
            'source_url' => route('home'),
            'lead_type' => $popup->lead_type,
            'email' => 'same@example.com',
            'ip_address' => '203.0.113.10',
            'user_agent' => 'PHPUnit',
            'metadata' => [],
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'same@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertSame(1, PopupLead::query()->count());
    }

    public function test_suppression_via_global_cookie(): void
    {
        $popup = Popup::factory()->create([
            'suppression_scope' => 'all_lead_popups',
            'target_pages' => ['home'],
            'form_fields' => ['email'],
        ]);

        $this->from(route('home'))
            ->withCookie('nojo_lead_captured', '1')
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('popup');

        $this->assertDatabaseCount('popup_leads', 0);
    }

    public function test_correct_cookie_creation(): void
    {
        $popup = Popup::factory()->create([
            'slug' => 'cookie-check',
            'target_pages' => ['home'],
            'form_fields' => ['email'],
            'suppression_scope' => 'this_popup_only',
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertCookie('nojo_popup_submitted_cookie_check', '1');
    }

    public function test_audit_logger_is_triggered(): void
    {
        $popup = Popup::factory()->create([
            'slug' => 'audit-popup',
            'target_pages' => ['home'],
            'form_fields' => ['email'],
        ]);

        $logger = Mockery::mock(SecurityAuditLogger::class);
        $logger->shouldReceive('log')
            ->once()
            ->withArgs(function (
                string $event,
                $request,
                $userId,
                $entityType,
                $entityId,
                array $context
            ) use ($popup): bool {
                return $event === 'popup_lead_created'
                    && $userId === null
                    && $entityType === 'popup_lead'
                    && is_int($entityId)
                    && $context['popup_id'] === $popup->id
                    && $context['popup_slug'] === $popup->slug
                    && $context['page_key'] === 'home';
            });

        $this->app->instance(SecurityAuditLogger::class, $logger);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'));
    }

    public function test_authenticated_user_can_submit_authenticated_popup(): void
    {
        $popup = Popup::factory()->create([
            'audience' => 'authenticated',
            'target_pages' => ['home'],
            'form_fields' => ['email'],
        ]);
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'member@example.com',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('popup_leads', [
            'popup_id' => $popup->id,
            'email' => 'member@example.com',
        ]);
    }
}
