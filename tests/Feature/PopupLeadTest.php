<?php

namespace Tests\Feature;

use App\Models\Acquisition;
use App\Models\AcquisitionPath;
use App\Models\AcquisitionContact;
use App\Models\AcquisitionCompany;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\Popup;
use App\Models\PopupLead;
use App\Models\Service;
use App\Models\User;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Fakes\Communications\FakeMarketingProvider;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\TestCase;

class PopupLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_submission(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);

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

        $lead = PopupLead::query()->latest('id')->firstOrFail();
        $contact = AcquisitionContact::query()->firstOrFail();

        $this->assertSame($contact->id, $lead->acquisition_contact_id);
        $this->assertSame('popup@example.com', $contact->primary_email);
        $this->assertSame('5551212', $contact->primary_phone);
        $this->assertSame('Jameel', $contact->display_name);
        $this->assertNull($contact->acquisition_company_id);
        $this->assertNull($contact->website_url_snapshot);
        $this->assertSame(0, AcquisitionCompany::query()->count());

        $this->assertDatabaseHas('acquisition_sources', [
            'acquisition_contact_id' => $contact->id,
            'source_type' => 'popup_submission',
            'source_table' => 'popup_leads',
            'source_record_id' => $lead->id,
            'page_key' => 'home',
            'source_url' => route('home'),
        ]);

        $this->assertDatabaseHas('acquisition_events', [
            'acquisition_contact_id' => $contact->id,
            'event_type' => 'popup_submission',
            'related_table' => 'popup_leads',
            'related_id' => $lead->id,
        ]);

        $event = CommunicationEvent::query()->where('event_key', 'popup.submitted')->firstOrFail();

        $this->assertSame($lead->id, $event->subject_id);
        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame(4, CommunicationDelivery::query()->where('communication_event_id', $event->id)->count());
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

    public function test_popup_submission_records_acquisition_context_in_metadata_when_explicit_context_is_present(): void
    {
        $acquisition = Acquisition::query()->create([
            'name' => 'General Inquiry Acquisition',
            'slug' => 'general-inquiry-acquisition',
            'is_active' => true,
        ]);

        $service = Service::query()->create([
            'acquisition_id' => $acquisition->id,
            'name' => 'General Contact',
            'slug' => 'general-contact',
            'is_active' => true,
        ]);

        $path = AcquisitionPath::query()->create([
            'acquisition_id' => $acquisition->id,
            'service_id' => $service->id,
            'name' => 'General Contact Home Popup',
            'slug' => 'general-contact-home-popup',
            'path_key' => 'general.contact.home-popup',
            'entry_type' => 'popup',
            'source_context' => 'home_popup',
            'is_active' => true,
        ]);

        $popup = Popup::factory()->create([
            'slug' => 'home-general-popup',
            'target_pages' => ['home'],
            'form_fields' => ['email'],
            'lead_type' => 'general',
        ]);

        $this->from(route('home'))
            ->withHeader('referer', route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'email' => 'popup-context@example.com',
                'acquisition_path_key' => $path->path_key,
            ])
            ->assertRedirect(route('home'));

        $lead = PopupLead::query()->latest('id')->firstOrFail();

        $this->assertSame($acquisition->id, $lead->metadata['acquisition_context']['acquisition_id'] ?? null);
        $this->assertSame($service->id, $lead->metadata['acquisition_context']['service_id'] ?? null);
        $this->assertSame($path->path_key, $lead->metadata['acquisition_context']['acquisition_path_key'] ?? null);
        $this->assertSame($popup->slug, $lead->metadata['acquisition_context']['source_popup_key'] ?? null);
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

    public function test_repeat_popup_submission_attaches_to_existing_contact_by_normalized_email_before_phone(): void
    {
        $first = Popup::factory()->create([
            'slug' => 'repeat-first',
            'target_pages' => ['home'],
            'form_fields' => ['name', 'email', 'phone'],
            'suppression_scope' => 'this_popup_only',
        ]);

        $second = Popup::factory()->create([
            'slug' => 'repeat-second',
            'target_pages' => ['home'],
            'form_fields' => ['name', 'email', 'phone'],
            'suppression_scope' => 'this_popup_only',
        ]);

        $this->from(route('home'))
            ->withHeader('referer', route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $first->id,
                'page_key' => 'home',
                'name' => 'First Popup',
                'email' => 'Repeat@Example.com',
                'phone' => '(555) 1212',
            ])
            ->assertRedirect(route('home'));

        $this->from(route('home'))
            ->withHeader('referer', route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $second->id,
                'page_key' => 'home',
                'name' => 'Second Popup',
                'email' => 'repeat@example.com',
                'phone' => '999-9999',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseCount('popup_leads', 2);
        $this->assertDatabaseCount('acquisition_contacts', 1);
        $this->assertDatabaseCount('acquisition_sources', 2);
        $this->assertDatabaseCount('acquisition_events', 2);

        $contact = AcquisitionContact::query()->firstOrFail();

        $this->assertSame('repeat@example.com', $contact->primary_email);
        $this->assertSame('5551212', $contact->primary_phone);
        $this->assertSame('First Popup', $contact->display_name);
        $this->assertSame(
            2,
            PopupLead::query()->where('acquisition_contact_id', $contact->id)->count()
        );
    }

    public function test_repeat_popup_submission_attaches_to_existing_contact_by_normalized_phone_when_email_differs(): void
    {
        $first = Popup::factory()->create([
            'slug' => 'phone-first',
            'target_pages' => ['home'],
            'form_fields' => ['name', 'email', 'phone'],
            'suppression_scope' => 'this_popup_only',
        ]);

        $second = Popup::factory()->create([
            'slug' => 'phone-second',
            'target_pages' => ['home'],
            'form_fields' => ['name', 'email', 'phone'],
            'suppression_scope' => 'this_popup_only',
        ]);

        $this->from(route('home'))
            ->withHeader('referer', route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $first->id,
                'page_key' => 'home',
                'name' => 'Phone Popup First',
                'email' => 'first@example.com',
                'phone' => '(555) 1212',
            ])
            ->assertRedirect(route('home'));

        $this->from(route('home'))
            ->withHeader('referer', route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $second->id,
                'page_key' => 'home',
                'name' => 'Phone Popup Second',
                'email' => 'second@example.com',
                'phone' => '555-1212',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseCount('popup_leads', 2);
        $this->assertDatabaseCount('acquisition_contacts', 1);

        $contact = AcquisitionContact::query()->firstOrFail();

        $this->assertSame('first@example.com', $contact->primary_email);
        $this->assertSame('5551212', $contact->primary_phone);
        $this->assertSame(
            2,
            PopupLead::query()->where('acquisition_contact_id', $contact->id)->count()
        );
    }
}
