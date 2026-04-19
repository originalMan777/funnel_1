<?php

namespace Tests\Unit\Campaigns;

use App\Models\AcquisitionContact;
use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\Lead;
use App\Models\Popup;
use App\Models\PopupLead;
use App\Services\Campaigns\CampaignAudienceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignAudienceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_a_lead_recipient(): void
    {
        $campaign = $this->createCampaign();
        $lead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => route('contact'),
            'entry_url' => route('contact'),
            'lead_status' => 'new',
            'type' => 'contact',
            'first_name' => 'Lead Person',
            'email' => 'lead@example.com',
            'payload' => [],
        ]);

        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'started_at' => now(),
        ]);

        $resolved = app(CampaignAudienceResolver::class)->resolve($enrollment);

        $this->assertSame([
            'email' => 'lead@example.com',
            'name' => 'Lead Person',
            'source_type' => 'lead',
            'source_id' => $lead->id,
        ], $resolved);
    }

    public function test_it_resolves_a_popup_lead_recipient(): void
    {
        $campaign = $this->createCampaign();
        $popup = Popup::factory()->create();
        $popupLead = PopupLead::query()->create([
            'popup_id' => $popup->id,
            'name' => 'Popup Person',
            'email' => 'popup@example.com',
            'metadata' => [],
        ]);

        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'popup_lead_id' => $popupLead->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'started_at' => now(),
        ]);

        $resolved = app(CampaignAudienceResolver::class)->resolve($enrollment);

        $this->assertSame([
            'email' => 'popup@example.com',
            'name' => 'Popup Person',
            'source_type' => 'popupLead',
            'source_id' => $popupLead->id,
        ], $resolved);
    }

    public function test_it_resolves_an_acquisition_contact_recipient(): void
    {
        $campaign = $this->createCampaign();
        $contact = AcquisitionContact::query()->create([
            'primary_email' => 'contact@example.com',
            'display_name' => 'Acquisition Contact',
        ]);

        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'acquisition_contact_id' => $contact->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'started_at' => now(),
        ]);

        $resolved = app(CampaignAudienceResolver::class)->resolve($enrollment);

        $this->assertSame([
            'email' => 'contact@example.com',
            'name' => 'Acquisition Contact',
            'source_type' => 'acquisitionContact',
            'source_id' => $contact->id,
        ], $resolved);
    }

    private function createCampaign(): Campaign
    {
        return Campaign::query()->create([
            'name' => 'Resolver Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);
    }
}
