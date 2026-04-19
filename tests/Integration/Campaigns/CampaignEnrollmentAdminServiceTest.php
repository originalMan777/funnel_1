<?php

namespace Tests\Integration\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Services\Campaigns\CampaignEnrollmentAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CampaignEnrollmentAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pause_requires_an_active_enrollment(): void
    {
        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $this->createCampaign()->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_PAUSED,
            'next_run_at' => now()->addHour(),
            'started_at' => now()->subHour(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only active enrollments can be paused.');

        app(CampaignEnrollmentAdminService::class)->pause($enrollment);
    }

    public function test_resume_requires_a_paused_enrollment_for_an_active_campaign_with_a_current_step(): void
    {
        $campaign = $this->createCampaign(Campaign::STATUS_PAUSED);
        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'current_step_order' => 9,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'next_run_at' => null,
            'started_at' => now()->subHour(),
        ]);

        try {
            app(CampaignEnrollmentAdminService::class)->resume($enrollment);
            $this->fail('Expected resume to reject a non-paused enrollment.');
        } catch (ValidationException $exception) {
            $this->assertSame('Only paused enrollments can be resumed.', $exception->errors()['status'][0]);
        }

        $enrollment->forceFill(['status' => CampaignEnrollment::STATUS_PAUSED])->save();

        try {
            app(CampaignEnrollmentAdminService::class)->resume($enrollment);
            $this->fail('Expected resume to reject a paused enrollment for a non-active campaign.');
        } catch (ValidationException $exception) {
            $this->assertSame('Only enrollments for active campaigns can be resumed.', $exception->errors()['campaign'][0]);
        }

        $campaign->forceFill(['status' => Campaign::STATUS_ACTIVE])->save();
        $enrollment = $enrollment->fresh();

        try {
            app(CampaignEnrollmentAdminService::class)->resume($enrollment);
            $this->fail('Expected resume to reject a missing current step.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This enrollment cannot be resumed because its current step is unavailable.',
                $exception->errors()['current_step_order'][0]
            );
        }
    }

    public function test_exit_rejects_completed_or_exited_enrollments(): void
    {
        $campaign = $this->createCampaign();

        foreach ([CampaignEnrollment::STATUS_COMPLETED, CampaignEnrollment::STATUS_EXITED] as $status) {
            $enrollment = CampaignEnrollment::query()->create([
                'campaign_id' => $campaign->id,
                'current_step_order' => 1,
                'status' => $status,
                'next_run_at' => null,
                'started_at' => now()->subHour(),
                'completed_at' => now()->subMinutes(5),
            ]);

            try {
                app(CampaignEnrollmentAdminService::class)->exit($enrollment);
                $this->fail('Expected exit to reject completed or exited enrollments.');
            } catch (ValidationException $exception) {
                $this->assertSame(
                    'Completed or exited enrollments cannot be exited again.',
                    $exception->errors()['status'][0]
                );
            }
        }
    }

    private function createCampaign(string $status = Campaign::STATUS_ACTIVE): Campaign
    {
        $campaign = Campaign::query()->create([
            'name' => 'Admin Campaign',
            'status' => $status,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'contact.requested',
        ]);

        CampaignStep::query()->create([
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_amount' => 1,
            'delay_unit' => 'days',
            'send_mode' => CampaignStep::SEND_MODE_CUSTOM,
            'subject' => 'Follow up',
            'html_body' => '<p>Hello</p>',
            'text_body' => 'Hello',
            'is_enabled' => true,
        ]);

        return $campaign;
    }
}
