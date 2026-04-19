<?php

namespace Tests\Feature\Admin;

use App\Jobs\ProcessCommunicationEventJob;
use App\Models\AcquisitionContact;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\CommunicationTemplate;
use App\Models\Lead;
use App\Models\MarketingContactSync;
use App\Models\User;
use App\Services\Communications\CommunicationService;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Communications\CommunicationSettingsRepository;
use App\Services\Communications\DTOs\MarketingActionResult;
use App\Services\Communications\DTOs\TransactionalSendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\Communications\FakeMarketingProvider;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\Fakes\Communications\StatefulMarketingProvider;
use Tests\Fakes\Communications\StatefulTransactionalEmailProvider;
use Tests\TestCase;

class CommunicationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        StatefulTransactionalEmailProvider::reset();
        StatefulMarketingProvider::reset();
    }

    private function createAcquisitionContact(string $email = 'contact@example.com'): AcquisitionContact
    {
        return AcquisitionContact::query()->create([
            'contact_type' => 'inbound',
            'state' => 'new',
            'primary_email' => $email,
            'display_name' => 'Contact '.$email,
        ]);
    }

    public function test_admin_communications_pages_load_for_authorized_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.communications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Communications/Overview'));

        $this->actingAs($admin)
            ->get(route('admin.communications.events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Communications/Events/Index'));

        $event = CommunicationEvent::query()->create([
            'event_key' => 'contact.requested',
            'subject_type' => Lead::class,
            'subject_id' => 1,
            'status' => CommunicationEvent::STATUS_FAILED,
            'payload' => ['message' => 'Need help'],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.communications.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Communications/Events/Show'));

        $this->actingAs($admin)
            ->get(route('admin.communications.deliveries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Communications/Deliveries/Index'));

        $delivery = CommunicationDelivery::query()->create([
            'communication_event_id' => $event->id,
            'action_key' => 'contact.user_confirmation',
            'channel' => 'email',
            'provider' => 'log',
            'recipient_email' => 'contact@example.com',
            'status' => CommunicationDelivery::STATUS_FAILED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.communications.deliveries.show', $delivery))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Communications/Deliveries/Show'));

        $this->actingAs($admin)
            ->get(route('admin.communications.syncs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Communications/Syncs/Index'));

        $contact = $this->createAcquisitionContact();

        $sync = MarketingContactSync::query()->create([
            'acquisition_contact_id' => $contact->id,
            'provider' => 'mailchimp',
            'audience_key' => 'audience.general',
            'email' => 'contact@example.com',
            'last_sync_status' => MarketingContactSync::STATUS_FAILED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.communications.syncs.show', $sync))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Communications/Syncs/Show'));

        $this->actingAs($admin)
            ->get(route('admin.communications.settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Communications/Settings'));

        $this->actingAs($admin)
            ->get(route('admin.communications.templates.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Templates/Create')
                ->has('bindingDefinitions')
            );
    }

    public function test_non_admin_users_cannot_access_communications_admin_pages_or_actions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $event = CommunicationEvent::query()->create([
            'event_key' => 'contact.requested',
            'subject_type' => Lead::class,
            'subject_id' => 1,
            'status' => CommunicationEvent::STATUS_FAILED,
        ]);

        $this->actingAs($user)
            ->get(route('admin.communications.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.communications.events.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.communications.events.show', $event))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.communications.events.requeue', $event))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.communications.deliveries.show', CommunicationDelivery::query()->create([
                'communication_event_id' => $event->id,
                'action_key' => 'contact.user_confirmation',
                'channel' => 'email',
                'provider' => 'log',
                'recipient_email' => 'contact@example.com',
                'status' => CommunicationDelivery::STATUS_FAILED,
            ])))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.communications.syncs.show', MarketingContactSync::query()->create([
                'acquisition_contact_id' => $this->createAcquisitionContact('user-sync@example.com')->id,
                'provider' => 'mailchimp',
                'audience_key' => 'audience.general',
                'email' => 'contact@example.com',
                'last_sync_status' => MarketingContactSync::STATUS_FAILED,
            ])))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.communications.settings.update'), [
                'transactional_provider' => 'log',
                'marketing_provider' => 'null',
                'marketing_default_audience_key' => 'audience.general',
                'mailchimp_audiences' => [],
                'mailchimp_tags' => [],
                'mailchimp_triggers' => [],
            ])
            ->assertForbidden();
    }

    public function test_communication_settings_persist_for_admin_use(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put(route('admin.communications.settings.update'), [
                'transactional_provider' => 'postmark',
                'marketing_provider' => 'mailchimp',
                'admin_notification_email' => 'ops@example.com',
                'admin_notification_name' => 'Ops Team',
                'marketing_default_audience_key' => 'audience.general',
                'mailchimp_audiences' => [
                    ['key' => 'audience.general', 'value' => 'list_general'],
                ],
                'mailchimp_tags' => [
                    ['key' => 'tag.contact.requested', 'value' => 'contact_requested'],
                ],
                'mailchimp_triggers' => [
                    ['key' => 'trigger.contact.requested', 'audience_key' => 'audience.general', 'tags' => 'automation_contact_requested'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('communication_settings', [
            'key' => 'transactional_provider',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.communications.settings.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Settings')
                ->where('settings.transactional_provider', 'postmark')
                ->where('settings.marketing_provider', 'mailchimp')
                ->where('settings.admin_notification_email', 'ops@example.com')
                ->where('settings.admin_notification_name', 'Ops Team')
                ->where('defaults.transactional_provider', config('communications.transactional_provider'))
            );
    }

    public function test_communication_settings_repository_rejects_unknown_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CommunicationSettingsRepository::class)->putMany([
            'transactional_provider' => 'log',
            'unexpected_key' => 'should-not-save',
        ]);
    }

    public function test_admin_can_requeue_failed_event_and_retry_respects_existing_dedupe_behavior(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->app->bind(TransactionalEmailProvider::class, StatefulTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, StatefulMarketingProvider::class);

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

        app(CommunicationService::class)->processEvent($event->id);
        $event->refresh();

        $this->assertSame(CommunicationEvent::STATUS_PARTIAL_FAILURE, $event->status);

        $this->actingAs($admin)
            ->post(route('admin.communications.events.requeue', $event))
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(ProcessCommunicationEventJob::class);

        $event->refresh();

        $this->assertSame(CommunicationEvent::STATUS_PENDING, $event->status);

        app(CommunicationService::class)->processEvent($event->id);
        $event->refresh();

        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame(1, CommunicationDelivery::query()
            ->where('communication_event_id', $event->id)
            ->where('action_key', 'contact.user_confirmation')
            ->count());
        $this->assertSame(1, CommunicationDelivery::query()
            ->where('communication_event_id', $event->id)
            ->where('action_key', 'marketing.sync_contact')
            ->count());
    }

    public function test_failed_deliveries_do_not_get_misleading_sent_timestamps(): void
    {
        Queue::fake();
        $this->app->bind(TransactionalEmailProvider::class, StatefulTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, StatefulMarketingProvider::class);

        StatefulTransactionalEmailProvider::queueResult(
            'contact.user_confirmation',
            TransactionalSendResult::failure('stateful', 'Temporary provider issue'),
        );
        StatefulTransactionalEmailProvider::queueResult(
            'contact.admin_notification',
            TransactionalSendResult::failure('stateful', 'Temporary provider issue'),
        );
        StatefulMarketingProvider::queueResult(
            'marketing.sync_contact',
            MarketingActionResult::failure('stateful-marketing', 'Temporary marketing issue', [
                'audience_key' => 'audience.general',
            ]),
        );
        StatefulMarketingProvider::queueResult(
            'marketing.add_to_audience.contact.requested',
            MarketingActionResult::failure('stateful-marketing', 'Temporary marketing issue', [
                'audience_key' => 'audience.general',
            ]),
        );
        StatefulMarketingProvider::queueResult(
            'marketing.tags.contact',
            MarketingActionResult::failure('stateful-marketing', 'Temporary marketing issue', [
                'audience_key' => 'audience.general',
            ]),
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

        app(CommunicationService::class)->processEvent($event->id);

        $this->assertSame(0, CommunicationDelivery::query()
            ->where('communication_event_id', $event->id)
            ->whereNotNull('sent_at')
            ->count());
    }

    public function test_admin_listings_show_existing_event_delivery_and_sync_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);

        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => 'Jameel Campo',
                'email' => 'contact@example.com',
                'message' => 'Need help with a general question.',
            ]);

        $event = CommunicationEvent::query()->where('event_key', 'contact.requested')->firstOrFail();
        app(CommunicationService::class)->processEvent($event->id);

        $this->assertSame(1, MarketingContactSync::query()->count());

        $this->actingAs($admin)
            ->get(route('admin.communications.events.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Events/Index')
                ->has('events.data', 1)
            );

        $this->actingAs($admin)
            ->get(route('admin.communications.deliveries.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Deliveries/Index')
                ->has('deliveries.data')
            );

        $this->actingAs($admin)
            ->get(route('admin.communications.syncs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Syncs/Index')
                ->has('syncs.data', 1)
            );
    }

    public function test_communications_overview_loads_operational_health_stats_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $pendingEvent = CommunicationEvent::query()->create([
            'event_key' => 'lead.created',
            'subject_type' => Lead::class,
            'subject_id' => 1,
            'status' => CommunicationEvent::STATUS_PENDING,
        ]);
        CommunicationEvent::query()->create([
            'event_key' => 'lead.created',
            'subject_type' => Lead::class,
            'subject_id' => 2,
            'status' => CommunicationEvent::STATUS_PROCESSING,
        ]);
        CommunicationEvent::query()->create([
            'event_key' => 'lead.created',
            'subject_type' => Lead::class,
            'subject_id' => 3,
            'status' => CommunicationEvent::STATUS_PROCESSED,
        ]);
        CommunicationEvent::query()->create([
            'event_key' => 'lead.created',
            'subject_type' => Lead::class,
            'subject_id' => 4,
            'status' => CommunicationEvent::STATUS_PARTIAL_FAILURE,
        ]);
        CommunicationEvent::query()->create([
            'event_key' => 'lead.created',
            'subject_type' => Lead::class,
            'subject_id' => 5,
            'status' => CommunicationEvent::STATUS_FAILED,
        ]);

        CommunicationDelivery::query()->create([
            'communication_event_id' => $pendingEvent->id,
            'action_key' => 'contact.user_confirmation',
            'channel' => 'email',
            'provider' => 'log',
            'recipient_email' => 'sent@example.com',
            'status' => CommunicationDelivery::STATUS_SENT,
            'sent_at' => now(),
        ]);
        CommunicationDelivery::query()->create([
            'communication_event_id' => $pendingEvent->id,
            'action_key' => 'contact.admin_notification',
            'channel' => 'email',
            'provider' => 'log',
            'recipient_email' => 'failed@example.com',
            'status' => CommunicationDelivery::STATUS_FAILED,
        ]);

        MarketingContactSync::query()->create([
            'acquisition_contact_id' => $this->createAcquisitionContact('failed@example.com')->id,
            'provider' => 'mailchimp',
            'audience_key' => 'audience.general',
            'email' => 'failed@example.com',
            'last_sync_status' => MarketingContactSync::STATUS_FAILED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.communications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Overview')
                ->where('summary.events_pending', 1)
                ->where('summary.events_processing', 1)
                ->where('summary.events_processed', 1)
                ->where('summary.events_partial_failure', 1)
                ->where('summary.events_failed', 1)
                ->where('summary.recent_deliveries_sent', 1)
                ->where('summary.recent_deliveries_failed', 1)
                ->where('summary.marketing_syncs_failed', 1)
            );
    }

    public function test_admin_event_trace_page_and_filters_show_expected_communication_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);

        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => 'Jameel Campo',
                'email' => 'contact@example.com',
                'message' => 'Need help with a general question.',
            ]);

        $event = CommunicationEvent::query()->where('event_key', 'contact.requested')->firstOrFail();
        app(CommunicationService::class)->processEvent($event->id);
        $event->refresh();

        $this->actingAs($admin)
            ->get(route('admin.communications.events.show', $event))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Events/Show')
                ->where('event.id', $event->id)
                ->where('event.event_key', 'contact.requested')
                ->where('event.can_requeue', false)
                ->has('deliveries', 4)
            );

        $failedEvent = CommunicationEvent::query()->create([
            'event_key' => 'lead.created',
            'subject_type' => Lead::class,
            'subject_id' => $event->subject_id,
            'status' => CommunicationEvent::STATUS_FAILED,
            'payload' => ['source' => 'manual'],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.communications.events.index', ['status' => CommunicationEvent::STATUS_FAILED]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Events/Index')
                ->where('filters.status', CommunicationEvent::STATUS_FAILED)
                ->has('events.data', 1)
            );

        $this->actingAs($admin)
            ->get(route('admin.communications.deliveries.index', ['event_key' => 'contact.requested']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Deliveries/Index')
                ->where('filters.event_key', 'contact.requested')
                ->has('deliveries.data', 4)
            );

        $delivery = CommunicationDelivery::query()->where('communication_event_id', $event->id)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.communications.deliveries.show', $delivery))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Deliveries/Show')
                ->where('delivery.id', $delivery->id)
                ->where('delivery.event.id', $event->id)
            );

        $this->actingAs($admin)
            ->get(route('admin.communications.syncs.index', ['audience_key' => 'audience.general']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Syncs/Index')
                ->where('filters.audience_key', 'audience.general')
                ->has('syncs.data', 1)
            );

        $sync = MarketingContactSync::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.communications.syncs.show', $sync))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Syncs/Show')
                ->where('sync.id', $sync->id)
                ->where('sync.provider', $sync->provider)
            );

        $this->actingAs($admin)
            ->get(route('admin.communications.events.show', $failedEvent))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Events/Show')
                ->where('event.can_requeue', true)
            );
    }

    public function test_admin_template_binding_catalog_is_exposed_with_human_readable_labels(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $template = CommunicationTemplate::query()->create([
            'key' => 'contact-confirmation',
            'name' => 'Contact Confirmation',
            'channel' => CommunicationTemplate::CHANNEL_EMAIL,
            'category' => CommunicationTemplate::CATEGORY_TRANSACTIONAL,
            'status' => CommunicationTemplate::STATUS_ACTIVE,
        ]);

        $template->bindings()->create([
            'event_key' => 'contact.requested',
            'action_key' => 'contact.user_confirmation',
            'channel' => CommunicationTemplate::CHANNEL_EMAIL,
            'is_enabled' => true,
            'priority' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.communications.templates.edit', $template))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Templates/Edit')
                ->where('bindingDefinitions.1.event_key', 'lead.created')
                ->where('bindingDefinitions.1.label', 'Lead Created')
                ->where('bindingDefinitions.1.actions.0.label', 'User Confirmation')
                ->where('template.bindings.0.event_key', 'contact.requested')
                ->where('template.bindings.0.action_key', 'contact.user_confirmation')
                ->where('template.editor_version', null)
                ->has('template.versions', 0)
            );

        $this->actingAs($admin)
            ->get(route('admin.communications.templates.show', $template))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Communications/Templates/Show')
                ->where('template.bindings.0.event_label', 'Contact Form Submitted')
                ->where('template.bindings.0.action_label', 'User Confirmation')
            );
    }

    public function test_admin_template_binding_validation_rejects_invalid_event_action_pairs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.communications.templates.create'))
            ->post(route('admin.communications.templates.store'), [
                'key' => 'invalid-binding-template',
                'name' => 'Invalid Binding Template',
                'status' => CommunicationTemplate::STATUS_DRAFT,
                'bindings' => [
                    [
                        'event_key' => 'lead.created',
                        'action_key' => 'contact.user_confirmation',
                        'is_enabled' => true,
                        'priority' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.communications.templates.create'))
            ->assertSessionHasErrors([
                'bindings.0.action_key',
            ]);

        $this->assertDatabaseMissing('communication_templates', [
            'key' => 'invalid-binding-template',
        ]);
    }
}
