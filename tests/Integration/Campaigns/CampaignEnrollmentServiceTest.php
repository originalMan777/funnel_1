<?php

namespace Tests\Integration\Campaigns;

use App\Models\AcquisitionContact;
use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Models\Lead;
use App\Models\Popup;
use App\Models\PopupLead;
use App\Services\Campaigns\CampaignEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignEnrollmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_enrolls_only_matching_active_campaigns_and_sets_the_first_enabled_step_context_and_schedule(): void
    {
        $startedAt = now()->startOfMinute();
        $this->travelTo($startedAt);

        $contact = AcquisitionContact::query()->create([
            'primary_email' => 'campaign@example.com',
            'display_name' => 'Campaign Contact',
        ]);

        $lead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => route('contact'),
            'entry_url' => route('contact'),
            'lead_status' => 'new',
            'type' => 'contact',
            'first_name' => 'Lead Person',
            'email' => 'campaign@example.com',
            'acquisition_contact_id' => $contact->id,
            'payload' => ['message' => 'Need follow up'],
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
            'lead_type' => 'general',
            'name' => 'Popup Person',
            'email' => 'popup@example.com',
            'acquisition_contact_id' => $contact->id,
            'metadata' => ['source' => 'test'],
        ]);

        $matchingCampaign = Campaign::query()->create([
            'name' => 'Matching Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $matchingCampaign->id,
            'step_order' => 1,
            'delay_amount' => 3,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Disabled first step',
            'html_body' => '<p>Disabled</p>',
            'text_body' => 'Disabled',
            'is_enabled' => false,
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $matchingCampaign->id,
            'step_order' => 2,
            'delay_amount' => 5,
            'delay_unit' => 'hours',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'First enabled step',
            'html_body' => '<p>Enabled</p>',
            'text_body' => 'Enabled',
            'is_enabled' => true,
        ]);

        Campaign::query()->create([
            'name' => 'Wrong Trigger',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'lead.created',
        ]);

        Campaign::query()->create([
            'name' => 'Paused Campaign',
            'status' => Campaign::STATUS_PAUSED,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        app(CampaignEnrollmentService::class)->enrollForTrigger('contact.requested', [
            'lead_id' => $lead->id,
            'popup_lead_id' => $popupLead->id,
            'acquisition_contact_id' => $contact->id,
        ]);

        $this->assertDatabaseCount('campaign_enrollments', 1);

        $enrollment = CampaignEnrollment::query()->firstOrFail();

        $this->assertSame($matchingCampaign->id, $enrollment->campaign_id);
        $this->assertSame($lead->id, $enrollment->lead_id);
        $this->assertSame($popupLead->id, $enrollment->popup_lead_id);
        $this->assertSame($contact->id, $enrollment->acquisition_contact_id);
        $this->assertSame(2, $enrollment->current_step_order);
        $this->assertSame(CampaignEnrollment::STATUS_ACTIVE, $enrollment->status);
        $this->assertTrue($enrollment->started_at->equalTo($startedAt));
        $this->assertTrue($enrollment->next_run_at->equalTo($startedAt->copy()->addHours(5)));
        $this->assertNull($enrollment->completed_at);
        $this->assertNull($enrollment->exit_reason);
    }

    public function test_it_marks_enrollment_completed_when_campaign_has_no_enabled_steps(): void
    {
        $startedAt = now()->startOfMinute();
        $this->travelTo($startedAt);

        $campaign = Campaign::query()->create([
            'name' => 'No Steps Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 1,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Disabled step',
            'html_body' => '<p>Disabled</p>',
            'text_body' => 'Disabled',
            'is_enabled' => false,
        ]);

        $enrollment = app(CampaignEnrollmentService::class)->createEnrollment($campaign, [
            'acquisition_contact_id' => AcquisitionContact::query()->create([
                'primary_email' => 'nosteps@example.com',
                'display_name' => 'No Steps',
            ])->id,
        ]);

        $enrollment->refresh();

        $this->assertSame(CampaignEnrollment::STATUS_COMPLETED, $enrollment->status);
        $this->assertSame('no_steps', $enrollment->exit_reason);
        $this->assertSame(1, $enrollment->current_step_order);
        $this->assertNull($enrollment->next_run_at);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertTrue($enrollment->started_at->equalTo($startedAt));
    }
}
