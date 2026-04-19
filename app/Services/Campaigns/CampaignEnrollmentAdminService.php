<?php

namespace App\Services\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class CampaignEnrollmentAdminService
{
    public function pause(CampaignEnrollment $enrollment): void
    {
        if ($enrollment->status !== CampaignEnrollment::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'status' => 'Only active enrollments can be paused.',
            ]);
        }

        $enrollment->forceFill([
            'status' => CampaignEnrollment::STATUS_PAUSED,
        ])->save();
    }

    public function resume(CampaignEnrollment $enrollment): void
    {
        if ($enrollment->status !== CampaignEnrollment::STATUS_PAUSED) {
            throw ValidationException::withMessages([
                'status' => 'Only paused enrollments can be resumed.',
            ]);
        }

        $enrollment->loadMissing([
            'campaign.steps' => fn ($query) => $query->enabled()->orderBy('step_order'),
        ]);

        if (! $enrollment->campaign instanceof Campaign || $enrollment->campaign->status !== Campaign::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'campaign' => 'Only enrollments for active campaigns can be resumed.',
            ]);
        }

        $currentStep = $enrollment->campaign->steps
            ->firstWhere('step_order', $enrollment->current_step_order);

        if (! $currentStep instanceof CampaignStep) {
            throw ValidationException::withMessages([
                'current_step_order' => 'This enrollment cannot be resumed because its current step is unavailable.',
            ]);
        }

        $enrollment->forceFill([
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'next_run_at' => $enrollment->next_run_at
                ?? $this->calculateNextRunAt(
                    (int) $currentStep->delay_amount,
                    (string) $currentStep->delay_unit,
                    now(),
                ),
        ])->save();
    }

    public function exit(CampaignEnrollment $enrollment): void
    {
        if (in_array($enrollment->status, [
            CampaignEnrollment::STATUS_COMPLETED,
            CampaignEnrollment::STATUS_EXITED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Completed or exited enrollments cannot be exited again.',
            ]);
        }

        $enrollment->forceFill([
            'status' => CampaignEnrollment::STATUS_EXITED,
            'completed_at' => now(),
            'exit_reason' => 'Manually exited by admin',
            'next_run_at' => null,
        ])->save();
    }

    private function calculateNextRunAt(
        int $delayAmount,
        string $delayUnit,
        CarbonInterface $from,
    ): CarbonInterface {
        $delayAmount = max(0, $delayAmount);

        return match ($delayUnit) {
            'hours' => $from->copy()->addHours($delayAmount),
            'weeks' => $from->copy()->addWeeks($delayAmount),
            default => $from->copy()->addDays($delayAmount),
        };
    }
}
