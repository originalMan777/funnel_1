<?php

namespace Tests\Feature;

use App\Models\AcquisitionContact;
use App\Models\AcquisitionCompany;
use App\Models\AcquisitionEvent;
use App\Models\Acquisition;
use App\Models\AcquisitionPath;
use App\Models\AcquisitionSource;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Service;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\Communications\FakeMarketingProvider;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\TestCase;

class PublicLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_lead_submission_persists_and_sets_cookie(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        [$slot, $box] = $this->createAssignedSlot('home_intro', LeadBox::factory()->resource()->active()->create());

        $this->from(route('home'))
            ->post(route('leads.store'), [
                'lead_box_id' => $box->id,
                'lead_slot_key' => $slot->key,
                'page_key' => 'home',
                'source_url' => 'https://example.com/home',
                'first_name' => 'Jameel',
                'email' => 'lead@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertCookie('nojo_lead_captured', '1');

        $this->assertDatabaseHas('leads', [
            'lead_box_id' => $box->id,
            'lead_slot_key' => 'home_intro',
            'page_key' => 'home',
            'type' => LeadBox::TYPE_RESOURCE,
            'first_name' => 'Jameel',
            'email' => 'lead@example.com',
        ]);

        $lead = Lead::query()->latest('id')->firstOrFail();
        $contact = AcquisitionContact::query()->firstOrFail();

        $this->assertSame($contact->id, $lead->acquisition_contact_id);
        $this->assertSame('lead@example.com', $contact->primary_email);
        $this->assertSame('Jameel', $contact->display_name);
        $this->assertNull($contact->acquisition_company_id);
        $this->assertNull($contact->website_url_snapshot);
        $this->assertSame(0, AcquisitionCompany::query()->count());

        $this->assertDatabaseHas('acquisition_sources', [
            'acquisition_contact_id' => $contact->id,
            'source_type' => 'lead_submission',
            'source_table' => 'leads',
            'source_record_id' => $lead->id,
            'page_key' => 'home',
            'source_url' => 'https://example.com/home',
        ]);

        $this->assertDatabaseHas('acquisition_events', [
            'acquisition_contact_id' => $contact->id,
            'event_type' => 'lead_submission',
            'related_table' => 'leads',
            'related_id' => $lead->id,
        ]);

        $event = CommunicationEvent::query()->where('event_key', 'lead.created')->firstOrFail();

        $this->assertSame($lead->id, $event->subject_id);
        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame(4, CommunicationDelivery::query()->where('communication_event_id', $event->id)->count());
    }

    public function test_service_lead_submission_requires_phone_and_persists_payload(): void
    {
        [$slot, $box] = $this->createAssignedSlot('home_mid', LeadBox::factory()->service()->active()->create());

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'first_name' => 'Jameel',
            'email' => 'lead@example.com',
        ])->assertSessionHasErrors('phone');

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'page_key' => 'home',
            'source_url' => 'https://example.com/home',
            'first_name' => 'Jameel',
            'email' => 'lead@example.com',
            'phone' => '555-1212',
            'message' => 'Need advice',
        ])->assertRedirect();

        $lead = Lead::query()->latest('id')->firstOrFail();
        $contact = AcquisitionContact::query()->firstOrFail();

        $this->assertSame(LeadBox::TYPE_SERVICE, $lead->type);
        $this->assertSame($contact->id, $lead->acquisition_contact_id);
        $this->assertSame('lead@example.com', $contact->primary_email);
        $this->assertSame('5551212', $contact->primary_phone);
        $this->assertNull($contact->acquisition_company_id);
        $this->assertNull($contact->website_url_snapshot);
        $this->assertSame('555-1212', $lead->payload['phone']);
        $this->assertSame('Need advice', $lead->payload['message']);

        $this->assertDatabaseHas('acquisition_sources', [
            'acquisition_contact_id' => $contact->id,
            'source_type' => 'lead_submission',
            'source_table' => 'leads',
            'source_record_id' => $lead->id,
        ]);

        $this->assertDatabaseHas('acquisition_events', [
            'acquisition_contact_id' => $contact->id,
            'event_type' => 'lead_submission',
            'related_table' => 'leads',
            'related_id' => $lead->id,
        ]);
    }

    public function test_repeated_lead_submissions_attach_to_existing_contact_by_normalized_email_before_phone(): void
    {
        [$slot, $box] = $this->createAssignedSlot('home_mid', LeadBox::factory()->service()->active()->create());

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'page_key' => 'home',
            'source_url' => 'https://example.com/home',
            'first_name' => 'First Lead',
            'email' => 'Repeat@Example.com',
            'phone' => '(555) 1212',
            'message' => 'First',
        ])->assertRedirect();

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'page_key' => 'home',
            'source_url' => 'https://example.com/home',
            'first_name' => 'Second Lead',
            'email' => 'repeat@example.com',
            'phone' => '999-9999',
            'message' => 'Second',
        ])->assertRedirect();

        $this->assertDatabaseCount('leads', 2);
        $this->assertDatabaseCount('acquisition_contacts', 1);
        $this->assertDatabaseCount('acquisition_sources', 2);
        $this->assertDatabaseCount('acquisition_events', 2);

        $contact = AcquisitionContact::query()->firstOrFail();

        $this->assertSame('repeat@example.com', $contact->primary_email);
        $this->assertSame('5551212', $contact->primary_phone);
        $this->assertSame('First Lead', $contact->display_name);

        $this->assertSame(
            2,
            Lead::query()->where('acquisition_contact_id', $contact->id)->count()
        );
    }

    public function test_repeated_lead_submissions_attach_to_existing_contact_by_normalized_phone_when_email_differs(): void
    {
        [$slot, $box] = $this->createAssignedSlot('home_mid', LeadBox::factory()->service()->active()->create());

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'page_key' => 'home',
            'source_url' => 'https://example.com/home',
            'first_name' => 'Phone Match First',
            'email' => 'first@example.com',
            'phone' => '(555) 1212',
            'message' => 'First',
        ])->assertRedirect();

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'page_key' => 'home',
            'source_url' => 'https://example.com/home',
            'first_name' => 'Phone Match Second',
            'email' => 'second@example.com',
            'phone' => '555-1212',
            'message' => 'Second',
        ])->assertRedirect();

        $this->assertDatabaseCount('leads', 2);
        $this->assertDatabaseCount('acquisition_contacts', 1);

        $contact = AcquisitionContact::query()->firstOrFail();

        $this->assertSame('first@example.com', $contact->primary_email);
        $this->assertSame('5551212', $contact->primary_phone);
        $this->assertSame(
            2,
            Lead::query()->where('acquisition_contact_id', $contact->id)->count()
        );
    }

    public function test_disabled_slots_cannot_accept_leads(): void
    {
        $slot = LeadSlot::factory()->create(['key' => 'home_intro', 'is_enabled' => false]);
        $box = LeadBox::factory()->resource()->active()->create();
        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
        ]);

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'first_name' => 'Jameel',
            'email' => 'lead@example.com',
        ])->assertSessionHasErrors('lead_slot_key');
    }

    public function test_mismatched_assignment_is_rejected(): void
    {
        $slot = LeadSlot::factory()->create(['key' => 'home_intro', 'is_enabled' => true]);
        $assignedBox = LeadBox::factory()->resource()->active()->create();
        $wrongBox = LeadBox::factory()->resource()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $assignedBox->id,
        ]);

        $this->post(route('leads.store'), [
            'lead_box_id' => $wrongBox->id,
            'lead_slot_key' => $slot->key,
            'first_name' => 'Jameel',
            'email' => 'lead@example.com',
        ])->assertSessionHasErrors('lead_box_id');
    }

    public function test_cross_type_assigned_lead_boxes_are_accepted_for_submission(): void
    {
        $slot = LeadSlot::factory()->create(['key' => 'home_intro', 'is_enabled' => true]);
        $box = LeadBox::factory()->service()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
        ]);

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'page_key' => 'home',
            'source_url' => 'https://example.com/home',
            'first_name' => 'Jameel',
            'email' => 'lead@example.com',
            'phone' => '555-1212',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'type' => LeadBox::TYPE_SERVICE,
            'email' => 'lead@example.com',
        ]);
    }

    public function test_inactive_lead_boxes_are_rejected_even_if_assigned(): void
    {
        [$slot, $box] = $this->createAssignedSlot('home_intro', LeadBox::factory()->resource()->create([
            'status' => LeadBox::STATUS_INACTIVE,
        ]));

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'first_name' => 'Jameel',
            'email' => 'lead@example.com',
        ])->assertSessionHasErrors('lead_box_id');
    }

    public function test_lead_submission_resolves_assignment_level_acquisition_context(): void
    {
        $acquisition = Acquisition::query()->create([
            'name' => 'Buyer Acquisition',
            'slug' => 'buyer-acquisition',
            'is_active' => true,
        ]);

        $service = Service::query()->create([
            'acquisition_id' => $acquisition->id,
            'name' => 'Buyer Consultation',
            'slug' => 'buyer-consultation',
            'is_active' => true,
        ]);

        $path = AcquisitionPath::query()->create([
            'acquisition_id' => $acquisition->id,
            'service_id' => $service->id,
            'name' => 'Buyer Consultation Blog Inline',
            'slug' => 'buyer-consultation-blog-inline',
            'path_key' => 'buyer.consultation.blog-inline',
            'entry_type' => 'lead_slot',
            'source_context' => 'blog_inline',
            'is_active' => true,
        ]);

        $slot = LeadSlot::factory()->create(['key' => 'home_mid', 'is_enabled' => true]);
        $box = LeadBox::factory()->service()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
            'acquisition_id' => $acquisition->id,
            'service_id' => $service->id,
            'acquisition_path_id' => $path->id,
            'acquisition_path_key' => $path->path_key,
        ]);

        $this->post(route('leads.store'), [
            'lead_box_id' => $box->id,
            'lead_slot_key' => $slot->key,
            'page_key' => 'home',
            'source_url' => 'https://example.com/home',
            'first_name' => 'Mapped Lead',
            'email' => 'mapped@example.com',
            'phone' => '555-1212',
            'message' => 'Need buyer help',
        ])->assertRedirect();

        $lead = Lead::query()->latest('id')->firstOrFail();
        $event = AcquisitionEvent::query()->latest('id')->firstOrFail();
        $source = AcquisitionSource::query()->latest('id')->firstOrFail();

        $this->assertSame($acquisition->id, $lead->acquisition_id);
        $this->assertSame($service->id, $lead->service_id);
        $this->assertSame($path->id, $lead->acquisition_path_id);
        $this->assertSame($path->path_key, $lead->acquisition_path_key);
        $this->assertSame('home', $lead->source_page_key);
        $this->assertSame('home_mid', $lead->source_slot_key);
        $this->assertSame('https://example.com/home', $lead->entry_url);
        $this->assertSame('new', $lead->lead_status);

        $this->assertSame($path->path_key, $source->metadata['acquisition_path_key'] ?? null);
        $this->assertSame('home_mid', $source->metadata['source_slot_key'] ?? null);
        $this->assertSame($service->id, $event->details['service_id'] ?? null);
        $this->assertSame($path->path_key, $event->details['acquisition_path_key'] ?? null);
    }

    public function test_consultation_request_preserves_assignment_context_when_submitted_from_a_slot_flow(): void
    {
        $acquisition = Acquisition::query()->create([
            'name' => 'Seller Acquisition',
            'slug' => 'seller-acquisition',
            'is_active' => true,
        ]);

        $service = Service::query()->create([
            'acquisition_id' => $acquisition->id,
            'name' => 'Listing Consultation',
            'slug' => 'listing-consultation',
            'is_active' => true,
        ]);

        $path = AcquisitionPath::query()->create([
            'acquisition_id' => $acquisition->id,
            'service_id' => $service->id,
            'name' => 'Seller Listing Consult Home Inline',
            'slug' => 'seller-listing-consult-home-inline',
            'path_key' => 'seller.listing-consult.home-inline',
            'entry_type' => 'lead_slot',
            'source_context' => 'home_inline',
            'is_active' => true,
        ]);

        $slot = LeadSlot::factory()->create(['key' => 'home_mid', 'is_enabled' => true]);
        $box = LeadBox::factory()->service()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
            'acquisition_id' => $acquisition->id,
            'service_id' => $service->id,
            'acquisition_path_id' => $path->id,
            'acquisition_path_key' => $path->path_key,
        ]);

        $this->from(route('consultation.request'))
            ->withHeader('referer', route('consultation.request'))
            ->post(route('consultation.request.store'), [
                'name' => 'Consult Lead',
                'email' => 'consult@example.com',
                'phone' => '555-2121',
                'details' => 'Looking for listing advice.',
                'page_key' => 'home',
                'lead_slot_key' => 'home_mid',
            ])
            ->assertRedirect(route('consultation.request'));

        $lead = Lead::query()->latest('id')->firstOrFail();

        $this->assertSame('consultation', $lead->type);
        $this->assertSame('consultation_request', $lead->page_key);
        $this->assertSame('home', $lead->source_page_key);
        $this->assertSame('home_mid', $lead->source_slot_key);
        $this->assertSame($acquisition->id, $lead->acquisition_id);
        $this->assertSame($service->id, $lead->service_id);
        $this->assertSame($path->path_key, $lead->acquisition_path_key);
    }

    private function createAssignedSlot(string $key, LeadBox $box): array
    {
        $slot = LeadSlot::factory()->create([
            'key' => $key,
            'is_enabled' => true,
        ]);

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
        ]);

        return [$slot, $box];
    }
}
