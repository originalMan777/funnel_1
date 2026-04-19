<?php

namespace Tests\Integration\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\Lead;
use App\Services\Campaigns\CampaignRunnerService;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\Communications\FailingTransactionalEmailProvider;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\TestCase;

class CampaignRunnerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_due_processes_due_active_enrollments_and_advances_to_the_next_enabled_step(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $runAt = now()->startOfMinute();
        $this->travelTo($runAt);

        $campaign = Campaign::query()->create([
            'name' => 'Runner Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Step one',
            'html_body' => '<p>One</p>',
            'text_body' => 'One',
            'is_enabled' => true,
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 2,
            'delay_amount' => 2,
            'delay_unit' => 'weeks',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Step two',
            'html_body' => '<p>Two</p>',
            'text_body' => 'Two',
            'is_enabled' => true,
        ]);

        $dueEnrollment = $this->createLeadEnrollment($campaign, CampaignEnrollment::STATUS_ACTIVE, $runAt->copy()->subMinute(), 'due@example.com');
        $pausedEnrollment = $this->createLeadEnrollment($campaign, CampaignEnrollment::STATUS_PAUSED, $runAt->copy()->subMinute(), 'paused@example.com');

        $processed = app(CampaignRunnerService::class)->runDue();

        $dueEnrollment->refresh();
        $pausedEnrollment->refresh();

        $this->assertSame(1, $processed);
        $this->assertSame(2, $dueEnrollment->current_step_order);
        $this->assertTrue($dueEnrollment->next_run_at->equalTo($runAt->copy()->addWeeks(2)));
        $this->assertSame(CampaignEnrollment::STATUS_ACTIVE, $dueEnrollment->status);
        $this->assertSame(1, CommunicationEvent::query()->count());
        $this->assertSame(1, CommunicationDelivery::query()->count());
        $this->assertSame(1, $pausedEnrollment->current_step_order);
        $this->assertSame(CampaignEnrollment::STATUS_PAUSED, $pausedEnrollment->status);
    }

    public function test_it_marks_completed_when_no_next_enabled_step_exists(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);

        $campaign = Campaign::query()->create([
            'name' => 'Single Step Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Only step',
            'html_body' => '<p>Done</p>',
            'text_body' => 'Done',
            'is_enabled' => true,
        ]);

        $enrollment = $this->createLeadEnrollment($campaign, CampaignEnrollment::STATUS_ACTIVE, now()->subMinute(), 'complete@example.com');

        app(CampaignRunnerService::class)->runEnrollment($enrollment);

        $enrollment->refresh();

        $this->assertSame(CampaignEnrollment::STATUS_COMPLETED, $enrollment->status);
        $this->assertSame('completed', $enrollment->exit_reason);
        $this->assertNull($enrollment->next_run_at);
        $this->assertNotNull($enrollment->completed_at);
    }

    public function test_it_exits_enrollment_when_campaign_is_not_active(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);

        $campaign = Campaign::query()->create([
            'name' => 'Paused Campaign',
            'status' => Campaign::STATUS_PAUSED,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Step one',
            'html_body' => '<p>One</p>',
            'text_body' => 'One',
            'is_enabled' => true,
        ]);

        $enrollment = $this->createLeadEnrollment($campaign, CampaignEnrollment::STATUS_ACTIVE, now()->subMinute(), 'inactive@example.com');

        app(CampaignRunnerService::class)->runEnrollment($enrollment);

        $enrollment->refresh();

        $this->assertSame(CampaignEnrollment::STATUS_EXITED, $enrollment->status);
        $this->assertSame('campaign_inactive', $enrollment->exit_reason);
        $this->assertNull($enrollment->next_run_at);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertDatabaseCount('communication_events', 0);
    }

    public function test_it_marks_completed_when_the_current_step_is_missing(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);

        $campaign = Campaign::query()->create([
            'name' => 'Missing Step Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 2,
            'delay_amount' => 1,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Existing step',
            'html_body' => '<p>Existing</p>',
            'text_body' => 'Existing',
            'is_enabled' => true,
        ]);

        $enrollment = $this->createLeadEnrollment($campaign, CampaignEnrollment::STATUS_ACTIVE, now()->subMinute(), 'missing@example.com');

        app(CampaignRunnerService::class)->runEnrollment($enrollment);

        $enrollment->refresh();

        $this->assertSame(CampaignEnrollment::STATUS_COMPLETED, $enrollment->status);
        $this->assertSame('no_current_step', $enrollment->exit_reason);
        $this->assertNull($enrollment->next_run_at);
        $this->assertDatabaseCount('communication_events', 0);
    }

    public function test_it_leaves_non_active_enrollments_unchanged(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);

        $campaign = Campaign::query()->create([
            'name' => 'Paused Enrollment Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Step one',
            'html_body' => '<p>One</p>',
            'text_body' => 'One',
            'is_enabled' => true,
        ]);

        $enrollment = $this->createLeadEnrollment($campaign, CampaignEnrollment::STATUS_PAUSED, now()->subMinute(), 'paused@example.com');
        $originalNextRunAt = $enrollment->next_run_at;

        app(CampaignRunnerService::class)->runEnrollment($enrollment);

        $enrollment->refresh();

        $this->assertSame(CampaignEnrollment::STATUS_PAUSED, $enrollment->status);
        $this->assertTrue($enrollment->next_run_at->equalTo($originalNextRunAt));
        $this->assertDatabaseCount('communication_events', 0);
    }

    public function test_it_marks_failed_when_recipient_cannot_be_resolved(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);

        $campaign = Campaign::query()->create([
            'name' => 'Unresolvable Recipient Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Step one',
            'html_body' => '<p>One</p>',
            'text_body' => 'One',
            'is_enabled' => true,
        ]);

        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'next_run_at' => now()->subMinute(),
            'started_at' => now()->subHour(),
        ]);

        app(CampaignRunnerService::class)->runEnrollment($enrollment);

        $enrollment->refresh();

        $this->assertSame(CampaignEnrollment::STATUS_FAILED, $enrollment->status);
        $this->assertSame('recipient_unresolvable', $enrollment->exit_reason);
        $this->assertDatabaseCount('communication_events', 0);
    }

    public function test_it_marks_failed_when_step_content_is_unavailable(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);

        $campaign = Campaign::query()->create([
            'name' => 'Unavailable Content Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => null,
            'html_body' => null,
            'text_body' => null,
            'is_enabled' => true,
        ]);

        $enrollment = $this->createLeadEnrollment($campaign, CampaignEnrollment::STATUS_ACTIVE, now()->subMinute(), 'nocontent@example.com');

        app(CampaignRunnerService::class)->runEnrollment($enrollment);

        $enrollment->refresh();

        $this->assertSame(CampaignEnrollment::STATUS_FAILED, $enrollment->status);
        $this->assertSame('step_content_unavailable', $enrollment->exit_reason);
        $this->assertDatabaseCount('communication_events', 0);
    }

    public function test_it_marks_failed_when_the_provider_send_fails_and_does_not_advance_steps(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FailingTransactionalEmailProvider::class);

        $campaign = Campaign::query()->create([
            'name' => 'Failing Delivery Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Step one',
            'html_body' => '<p>One</p>',
            'text_body' => 'One',
            'is_enabled' => true,
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 2,
            'delay_amount' => 1,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Step two',
            'html_body' => '<p>Two</p>',
            'text_body' => 'Two',
            'is_enabled' => true,
        ]);

        $enrollment = $this->createLeadEnrollment($campaign, CampaignEnrollment::STATUS_ACTIVE, now()->subMinute(), 'failure@example.com');

        app(CampaignRunnerService::class)->runEnrollment($enrollment);

        $enrollment->refresh();
        $event = CommunicationEvent::query()->firstOrFail();
        $delivery = CommunicationDelivery::query()->firstOrFail();

        $this->assertSame(CampaignEnrollment::STATUS_FAILED, $enrollment->status);
        $this->assertSame('delivery_failed', $enrollment->exit_reason);
        $this->assertSame(1, $enrollment->current_step_order);
        $this->assertSame(CommunicationEvent::STATUS_FAILED, $event->status);
        $this->assertSame(CommunicationDelivery::STATUS_FAILED, $delivery->status);
        $this->assertSame('Simulated provider failure for campaign.step.send', $delivery->error_message);
    }

    private function createLeadEnrollment(Campaign $campaign, string $status, CarbonInterface $nextRunAt, string $email): CampaignEnrollment
    {
        $lead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => route('contact'),
            'entry_url' => route('contact'),
            'lead_status' => 'new',
            'type' => 'contact',
            'first_name' => 'Campaign Lead',
            'email' => $email,
            'payload' => ['message' => 'Campaign test'],
        ]);

        return CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
            'current_step_order' => 1,
            'status' => $status,
            'next_run_at' => $nextRunAt,
            'started_at' => now()->subHour(),
        ]);
    }
}
