<?php

namespace Tests\Feature\Communications;

use App\Jobs\ProcessCommunicationEventJob;
use App\Models\AcquisitionContact;
use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\MarketingContactSync;
use App\Models\Popup;
use App\Models\PopupLead;
use App\Services\Communications\CommunicationService;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Communications\DTOs\MarketingActionResult;
use App\Services\Communications\DTOs\TransactionalSendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\Communications\FailingTransactionalEmailProvider;
use Tests\Fakes\Communications\FakeMarketingProvider;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\Fakes\Communications\StatefulMarketingProvider;
use Tests\Fakes\Communications\StatefulTransactionalEmailProvider;
use Tests\TestCase;

class CommunicationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StatefulTransactionalEmailProvider::reset();
        StatefulMarketingProvider::reset();
    }

    public function test_contact_submission_queues_processing_instead_of_sending_in_request(): void
    {
        Queue::fake();
        $this->app->bind(TransactionalEmailProvider::class, FailingTransactionalEmailProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => 'Jameel Campo',
                'email' => 'contact@example.com',
                'message' => 'Need help with a general question.',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('success');

        $lead = Lead::query()->where('type', 'contact')->firstOrFail();
        $event = CommunicationEvent::query()->where('event_key', 'contact.requested')->firstOrFail();

        $this->assertSame($lead->id, $event->subject_id);
        $this->assertSame(Lead::class, $event->subject_type);
        $this->assertSame($lead->acquisition_contact_id, $event->acquisition_contact_id);
        $this->assertSame(CommunicationEvent::STATUS_PENDING, $event->status);
        $this->assertDatabaseCount('communication_deliveries', 0);

        Queue::assertPushed(ProcessCommunicationEventJob::class, function (ProcessCommunicationEventJob $job) use ($event) {
            return $job->communicationEventId === $event->id;
        });
    }

    public function test_supported_real_events_enroll_matching_campaigns_through_the_canonical_communication_trigger(): void
    {
        Queue::fake();

        $leadContact = AcquisitionContact::query()->create([
            'primary_email' => 'lead@example.com',
            'display_name' => 'Lead Person',
        ]);
        $contactContact = AcquisitionContact::query()->create([
            'primary_email' => 'contact@example.com',
            'display_name' => 'Contact Person',
        ]);
        $consultationContact = AcquisitionContact::query()->create([
            'primary_email' => 'consult@example.com',
            'display_name' => 'Consult Person',
        ]);
        $popupContact = AcquisitionContact::query()->create([
            'primary_email' => 'popup@example.com',
            'display_name' => 'Popup Person',
        ]);

        $leadCampaign = Campaign::query()->create([
            'name' => 'Lead Created Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'lead.created',
        ]);
        $contactCampaign = Campaign::query()->create([
            'name' => 'Contact Requested Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);
        $consultationCampaign = Campaign::query()->create([
            'name' => 'Consultation Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'lead.consultation_requested',
        ]);
        $popupCampaign = Campaign::query()->create([
            'name' => 'Popup Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_POPUP_LEADS,
            'entry_trigger' => 'popup.submitted',
        ]);

        foreach ([$leadCampaign, $contactCampaign, $consultationCampaign, $popupCampaign] as $campaign) {
            CampaignStep::query()->create([
                'campaign_id' => $campaign->id,
                'step_order' => 1,
                'delay_amount' => 0,
                'delay_unit' => CampaignStep::DELAY_UNIT_DAYS,
                'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
                'subject' => 'Follow up',
                'html_body' => '<p>Hi</p>',
                'text_body' => 'Hi',
                'is_enabled' => true,
            ]);
        }

        $lead = Lead::query()->create([
            'page_key' => 'resource_page',
            'source_url' => route('home'),
            'entry_url' => route('home'),
            'lead_status' => 'new',
            'type' => 'resource',
            'first_name' => 'Lead Person',
            'email' => 'lead@example.com',
            'acquisition_contact_id' => $leadContact->id,
            'payload' => [],
        ]);
        $contactLead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => route('contact'),
            'entry_url' => route('contact'),
            'lead_status' => 'new',
            'type' => 'contact',
            'first_name' => 'Contact Person',
            'email' => 'contact@example.com',
            'acquisition_contact_id' => $contactContact->id,
            'payload' => [
                'message' => 'Need help.',
            ],
        ]);
        $consultationLead = Lead::query()->create([
            'page_key' => 'consultation_request',
            'source_url' => route('consultation.request'),
            'entry_url' => route('consultation.request'),
            'lead_status' => 'new',
            'type' => 'consultation',
            'first_name' => 'Consult Person',
            'email' => 'consult@example.com',
            'acquisition_contact_id' => $consultationContact->id,
            'payload' => [
                'phone' => '555-1212',
                'details' => 'Need advice.',
            ],
        ]);
        $popup = Popup::factory()->create([
            'target_pages' => ['home'],
            'form_fields' => ['name', 'email'],
            'audience' => 'guests',
        ]);
        $popupLead = PopupLead::query()->create([
            'popup_id' => $popup->id,
            'page_key' => 'home',
            'source_url' => route('home'),
            'lead_type' => 'newsletter',
            'name' => 'Popup Person',
            'email' => 'popup@example.com',
            'acquisition_contact_id' => $popupContact->id,
            'metadata' => [],
        ]);

        app(CommunicationService::class)->recordAndQueue('lead.created', $lead, $lead->acquisition_contact_id);
        app(CommunicationService::class)->recordAndQueue('contact.requested', $contactLead, $contactLead->acquisition_contact_id);
        app(CommunicationService::class)->recordAndQueue('lead.consultation_requested', $consultationLead, $consultationLead->acquisition_contact_id);
        app(CommunicationService::class)->recordAndQueue('popup.submitted', $popupLead, $popupLead->acquisition_contact_id);

        $leadEnrollment = CampaignEnrollment::query()->where('campaign_id', $leadCampaign->id)->firstOrFail();
        $contactEnrollment = CampaignEnrollment::query()->where('campaign_id', $contactCampaign->id)->firstOrFail();
        $consultationEnrollment = CampaignEnrollment::query()->where('campaign_id', $consultationCampaign->id)->firstOrFail();
        $popupEnrollment = CampaignEnrollment::query()->where('campaign_id', $popupCampaign->id)->firstOrFail();

        $this->assertSame($lead->id, $leadEnrollment->lead_id);
        $this->assertNull($leadEnrollment->popup_lead_id);
        $this->assertSame($leadContact->id, $leadEnrollment->acquisition_contact_id);

        $this->assertSame($contactLead->id, $contactEnrollment->lead_id);
        $this->assertNull($contactEnrollment->popup_lead_id);
        $this->assertSame($contactContact->id, $contactEnrollment->acquisition_contact_id);

        $this->assertSame($consultationLead->id, $consultationEnrollment->lead_id);
        $this->assertNull($consultationEnrollment->popup_lead_id);
        $this->assertSame($consultationContact->id, $consultationEnrollment->acquisition_contact_id);

        $this->assertNull($popupEnrollment->lead_id);
        $this->assertSame($popupLead->id, $popupEnrollment->popup_lead_id);
        $this->assertSame($popupContact->id, $popupEnrollment->acquisition_contact_id);
    }

    public function test_queued_job_processes_event_successfully(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        $lead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => route('contact'),
            'entry_url' => route('contact'),
            'lead_status' => 'new',
            'type' => 'contact',
            'first_name' => 'Jameel Campo',
            'email' => 'contact@example.com',
            'payload' => [
                'message' => 'Need help with a general question.',
            ],
        ]);

        $event = app(CommunicationService::class)->recordAndQueue(
            eventKey: 'contact.requested',
            subject: $lead,
            acquisitionContactId: null,
            payload: [
                'lead_id' => $lead->id,
            ],
        );

        $this->assertNotNull($event);

        $job = new ProcessCommunicationEventJob($event->id);
        $job->handle(app(CommunicationService::class));

        $event->refresh();

        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame(5, CommunicationDelivery::query()->where('communication_event_id', $event->id)->count());
        $this->assertDatabaseHas('communication_deliveries', [
            'communication_event_id' => $event->id,
            'channel' => 'marketing',
            'action_key' => 'marketing.sync_contact',
            'status' => CommunicationDelivery::STATUS_SENT,
        ]);
    }

    public function test_duplicate_processing_does_not_duplicate_successful_deliveries_for_transactional_or_marketing_actions(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, StatefulTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, StatefulMarketingProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        $lead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => route('contact'),
            'entry_url' => route('contact'),
            'lead_status' => 'new',
            'type' => 'contact',
            'first_name' => 'Jameel Campo',
            'email' => 'contact@example.com',
            'payload' => [
                'message' => 'Need help with a general question.',
            ],
        ]);

        $event = app(CommunicationService::class)->recordAndQueue(
            eventKey: 'contact.requested',
            subject: $lead,
            acquisitionContactId: null,
        );

        $this->assertNotNull($event);

        app(CommunicationService::class)->processEvent($event->id);
        app(CommunicationService::class)->processEvent($event->id);

        $event->refresh();

        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertDatabaseCount('communication_deliveries', 5);
        $this->assertSame([
            'contact.user_confirmation',
            'contact.admin_notification',
        ], StatefulTransactionalEmailProvider::$sentActionKeys);
        $this->assertSame([
            'marketing.sync_contact',
            'marketing.tags.contact',
            'marketing.trigger.contact.requested',
        ], StatefulMarketingProvider::$handledActionKeys);
    }

    public function test_partial_failure_can_be_retried_without_duplicating_successful_transactional_or_marketing_deliveries(): void
    {
        Queue::fake();
        $this->app->bind(TransactionalEmailProvider::class, StatefulTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, StatefulMarketingProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        StatefulTransactionalEmailProvider::queueResult(
            'contact.admin_notification',
            TransactionalSendResult::failure('stateful', 'Temporary provider issue'),
        );
        StatefulMarketingProvider::queueResult(
            'marketing.trigger.contact.requested',
            MarketingActionResult::failure('stateful-marketing', 'Temporary marketing issue', [
                'audience_key' => 'audience.general',
            ]),
        );

        $lead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => route('contact'),
            'entry_url' => route('contact'),
            'lead_status' => 'new',
            'type' => 'contact',
            'first_name' => 'Jameel Campo',
            'email' => 'contact@example.com',
            'payload' => [
                'message' => 'Need help with a general question.',
            ],
        ]);

        $event = app(CommunicationService::class)->recordAndQueue(
            eventKey: 'contact.requested',
            subject: $lead,
            acquisitionContactId: null,
        );

        $this->assertNotNull($event);

        $firstAttempt = app(CommunicationService::class)->processEvent($event->id);

        $event->refresh();

        $this->assertFalse($firstAttempt);
        $this->assertSame(CommunicationEvent::STATUS_PARTIAL_FAILURE, $event->status);
        $this->assertDatabaseCount('communication_deliveries', 5);

        $secondAttempt = app(CommunicationService::class)->processEvent($event->id);

        $event->refresh();

        $this->assertTrue($secondAttempt);
        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertDatabaseCount('communication_deliveries', 7);
        $this->assertSame([
            'contact.user_confirmation',
            'contact.admin_notification',
            'contact.admin_notification',
        ], StatefulTransactionalEmailProvider::$sentActionKeys);
        $this->assertSame([
            'marketing.sync_contact',
            'marketing.tags.contact',
            'marketing.trigger.contact.requested',
            'marketing.trigger.contact.requested',
        ], StatefulMarketingProvider::$handledActionKeys);

        $this->assertSame(1, CommunicationDelivery::query()
            ->where('communication_event_id', $event->id)
            ->where('action_key', 'contact.user_confirmation')
            ->count());
        $this->assertSame(1, CommunicationDelivery::query()
            ->where('communication_event_id', $event->id)
            ->where('action_key', 'marketing.sync_contact')
            ->count());
    }

    public function test_popup_submission_records_event_and_user_confirmation_when_email_exists(): void
    {
        Queue::fake();
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);

        $popup = Popup::factory()->create([
            'target_pages' => ['home'],
            'form_fields' => ['name', 'email'],
            'audience' => 'guests',
        ]);

        $this->from(route('home'))
            ->post(route('popup-leads.store'), [
                'popup_id' => $popup->id,
                'page_key' => 'home',
                'name' => 'Popup Person',
                'email' => 'popup@example.com',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('popupLeadSuccess');

        $popupLead = PopupLead::query()->firstOrFail();
        $event = CommunicationEvent::query()->where('event_key', 'popup.submitted')->firstOrFail();

        $this->assertSame($popupLead->id, $event->subject_id);
        $this->assertSame(PopupLead::class, $event->subject_type);
        $this->assertSame(CommunicationEvent::STATUS_PENDING, $event->status);

        Queue::assertPushed(ProcessCommunicationEventJob::class);
    }

    public function test_lead_box_submission_records_a_lead_created_event(): void
    {
        Queue::fake();
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        $leadBox = LeadBox::factory()->resource()->active()->create();
        $slot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);

        LeadAssignment::query()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $leadBox->id,
        ]);

        $this->from(route('home'))
            ->post(route('leads.store'), [
                'lead_box_id' => $leadBox->id,
                'lead_slot_key' => 'home_intro',
                'first_name' => 'Lead Person',
                'email' => 'lead@example.com',
                'source_url' => route('home'),
            ])
            ->assertRedirect(route('home'))
            ->assertCookie('nojo_lead_captured', '1')
            ->assertSessionHas('success');

        $lead = Lead::query()->where('type', LeadBox::TYPE_RESOURCE)->firstOrFail();
        $event = CommunicationEvent::query()->where('event_key', 'lead.created')->firstOrFail();

        $this->assertSame($lead->id, $event->subject_id);
        $this->assertSame(CommunicationEvent::STATUS_PENDING, $event->status);

        Queue::assertPushed(ProcessCommunicationEventJob::class, function (ProcessCommunicationEventJob $job) use ($event) {
            return $job->communicationEventId === $event->id;
        });
    }

    public function test_contact_processing_records_marketing_sync_state_and_coexists_with_transactional_work(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => 'Jameel Campo',
                'email' => 'contact@example.com',
                'message' => 'Need help with a general question.',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('success');

        $event = CommunicationEvent::query()->where('event_key', 'contact.requested')->firstOrFail();

        $processed = app(CommunicationService::class)->processEvent($event->id);

        $event->refresh();

        $this->assertTrue($processed);
        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame(5, CommunicationDelivery::query()->where('communication_event_id', $event->id)->count());

        $contactSync = MarketingContactSync::query()->where('acquisition_contact_id', $event->acquisition_contact_id)->firstOrFail();

        $this->assertSame('fake-marketing', $contactSync->provider);
        $this->assertSame(MarketingContactSync::STATUS_SYNCED, $contactSync->last_sync_status);
        $this->assertSame('contact@example.com', $contactSync->email);
    }
}
