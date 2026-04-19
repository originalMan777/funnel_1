<?php

namespace Tests\Feature\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationTemplateVersion;
use App\Models\Lead;
use App\Services\Campaigns\CampaignStepSendService;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\TestCase;

class CampaignStepSendServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
    }

    public function test_it_sends_a_template_step_through_the_existing_communication_pipeline(): void
    {
        $lead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => route('contact'),
            'entry_url' => route('contact'),
            'lead_status' => 'new',
            'type' => 'contact',
            'first_name' => 'Jameel',
            'email' => 'jameel@example.com',
            'payload' => [
                'message' => 'Need campaign follow-up.',
            ],
        ]);

        $campaign = Campaign::query()->create([
            'name' => 'Contact Nurture',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        $template = CommunicationTemplate::query()->create([
            'key' => 'campaign.contact.follow_up',
            'name' => 'Campaign Contact Follow Up',
            'channel' => CommunicationTemplate::CHANNEL_EMAIL,
            'category' => CommunicationTemplate::CATEGORY_TRANSACTIONAL,
            'status' => CommunicationTemplate::STATUS_ACTIVE,
        ]);

        $version = CommunicationTemplateVersion::query()->create([
            'communication_template_id' => $template->id,
            'version_number' => 1,
            'subject' => 'Hi {{ recipient.name }}',
            'preview_text' => 'Step {{ step.order }}',
            'headline' => 'Campaign {{ campaign.name }}',
            'html_body' => '<p>{{ lead.payload.message }}</p>',
            'text_body' => 'Contact: {{ recipient.email }}',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $template->forceFill([
            'current_version_id' => $version->id,
        ])->save();

        $step = CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_TEMPLATE,
            'template_id' => $template->id,
            'is_enabled' => true,
        ]);

        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'next_run_at' => now(),
            'started_at' => now(),
        ]);

        app(CampaignStepSendService::class)->sendStep($enrollment, $step);

        $event = CommunicationEvent::query()
            ->where('event_key', CampaignStepSendService::EVENT_KEY)
            ->firstOrFail();

        $delivery = CommunicationDelivery::query()
            ->where('communication_event_id', $event->id)
            ->firstOrFail();

        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame('jameel@example.com', $delivery->recipient_email);
        $this->assertSame('Hi Jameel', $delivery->subject);
        $this->assertSame($template->id, $delivery->communication_template_id);
        $this->assertSame($version->id, $delivery->communication_template_version_id);
        $this->assertSame(CommunicationDelivery::STATUS_SENT, $delivery->status);
        $this->assertSame(CampaignEnrollment::STATUS_ACTIVE, $enrollment->fresh()->status);
    }

    public function test_it_sends_a_custom_step_through_the_existing_communication_pipeline(): void
    {
        $lead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => route('contact'),
            'entry_url' => route('contact'),
            'lead_status' => 'new',
            'type' => 'contact',
            'first_name' => 'Jameel',
            'email' => 'custom@example.com',
            'payload' => [],
        ]);

        $campaign = Campaign::query()->create([
            'name' => 'Custom Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        $step = CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Custom hello',
            'html_body' => '<p>Plain campaign body</p>',
            'text_body' => 'Plain campaign body',
            'is_enabled' => true,
        ]);

        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'next_run_at' => now(),
            'started_at' => now(),
        ]);

        app(CampaignStepSendService::class)->sendStep($enrollment, $step);

        $event = CommunicationEvent::query()
            ->where('event_key', CampaignStepSendService::EVENT_KEY)
            ->firstOrFail();

        $delivery = CommunicationDelivery::query()
            ->where('communication_event_id', $event->id)
            ->firstOrFail();

        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame('custom@example.com', $delivery->recipient_email);
        $this->assertSame('Custom hello', $delivery->subject);
        $this->assertNull($delivery->communication_template_id);
        $this->assertNull($delivery->communication_template_version_id);
        $this->assertSame(CommunicationDelivery::STATUS_SENT, $delivery->status);
    }
}
