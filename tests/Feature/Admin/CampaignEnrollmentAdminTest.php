<?php

namespace Tests\Feature\Admin;

use App\Models\AcquisitionContact;
use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampaignEnrollmentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_view_campaign_enrollment_index_and_show_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $contact = AcquisitionContact::query()->create([
            'primary_email' => 'lead@example.com',
            'display_name' => 'Lead Person',
        ]);
        $campaign = Campaign::query()->create([
            'name' => 'Welcome Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'lead.created',
        ]);
        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 0,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Welcome',
            'html_body' => '<p>Hello</p>',
            'text_body' => 'Hello',
            'is_enabled' => true,
        ]);

        $lead = Lead::query()->create([
            'page_key' => 'home',
            'source_url' => route('home'),
            'entry_url' => route('home'),
            'lead_status' => 'new',
            'type' => 'resource',
            'first_name' => 'Lead Person',
            'email' => 'lead@example.com',
            'acquisition_contact_id' => $contact->id,
            'payload' => [],
        ]);

        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
            'acquisition_contact_id' => $contact->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'next_run_at' => now()->addHour(),
            'started_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.campaign-enrollments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CampaignEnrollments/Index')
                ->has('campaignOptions', 1)
                ->has('statusOptions', 5)
                ->has('enrollments.data', 1)
                ->where('enrollments.data.0.campaign.name', 'Welcome Campaign')
                ->where('enrollments.data.0.recipient.email', 'lead@example.com')
                ->where('enrollments.data.0.source.label', 'Lead')
            );

        $this->actingAs($admin)
            ->get(route('admin.campaign-enrollments.show', $enrollment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CampaignEnrollments/Show')
                ->where('enrollment.id', $enrollment->id)
                ->where('enrollment.campaign.name', 'Welcome Campaign')
                ->where('enrollment.recipient.name', 'Lead Person')
                ->where('enrollment.source.label', 'Lead')
                ->where('enrollment.current_step.label', 'Step 1')
                ->where('enrollment.status_label', 'Active')
                ->where('enrollment.can_pause', true)
            );
    }

    public function test_admin_can_pause_resume_and_exit_campaign_enrollments(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $campaign = Campaign::query()->create([
            'name' => 'Ops Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);
        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 2,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Follow up',
            'html_body' => '<p>Hi</p>',
            'text_body' => 'Hi',
            'is_enabled' => true,
        ]);

        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'next_run_at' => now()->addDay(),
            'started_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.campaign-enrollments.pause', $enrollment))
            ->assertRedirect();

        $enrollment->refresh();
        $this->assertSame(CampaignEnrollment::STATUS_PAUSED, $enrollment->status);
        $this->assertNotNull($enrollment->next_run_at);

        $enrollment->forceFill([
            'next_run_at' => null,
        ])->save();

        $this->actingAs($admin)
            ->post(route('admin.campaign-enrollments.resume', $enrollment))
            ->assertRedirect();

        $enrollment->refresh();
        $this->assertSame(CampaignEnrollment::STATUS_ACTIVE, $enrollment->status);
        $this->assertNotNull($enrollment->next_run_at);

        $this->actingAs($admin)
            ->post(route('admin.campaign-enrollments.exit', $enrollment))
            ->assertRedirect();

        $enrollment->refresh();
        $this->assertSame(CampaignEnrollment::STATUS_EXITED, $enrollment->status);
        $this->assertNull($enrollment->next_run_at);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertSame('Manually exited by admin', $enrollment->exit_reason);
    }
}
