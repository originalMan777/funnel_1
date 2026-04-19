<?php

namespace App\Services\Campaigns;

use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Services\Logging\StructuredEventLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CampaignRunnerService
{
    public function __construct(
        private readonly CampaignStepSendService $stepSendService,
        private readonly StructuredEventLogger $logger,
    ) {}

    public function runDue(): int
    {
        $count = 0;
        $startedAt = now();

        $this->logger->info('campaigns', 'campaigns', 'campaign_runner_started', [
            'entity_type' => 'campaign_runner',
            'entity_id' => 'due',
            'outcome' => 'started',
            'context' => [
                'started_at' => $startedAt->toIso8601String(),
                'chunk_size' => 100,
            ],
        ]);

        CampaignEnrollment::query()
            ->due()
            ->with([
                'campaign.steps' => fn ($query) => $query->enabled()->orderBy('step_order'),
                'lead',
                'popupLead',
                'acquisitionContact',
            ])
            ->orderBy('next_run_at')
            ->chunkById(100, function ($enrollments) use (&$count): void {
                foreach ($enrollments as $enrollment) {
                    $this->runEnrollment($enrollment);
                    $count++;
                }
            });

        $this->logger->info('campaigns', 'campaigns', 'campaign_runner_completed', [
            'entity_type' => 'campaign_runner',
            'entity_id' => 'due',
            'outcome' => 'completed',
            'context' => [
                'started_at' => $startedAt->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
                'processed_enrollments' => $count,
            ],
        ]);

        return $count;
    }

    public function runEnrollment(CampaignEnrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment): void {
            $enrollment->refresh();
            $enrollment->loadMissing([
                'campaign.steps' => fn ($query) => $query->enabled()->orderBy('step_order'),
                'lead',
                'popupLead',
                'acquisitionContact',
            ]);

            if ($enrollment->status !== CampaignEnrollment::STATUS_ACTIVE) {
                $this->logger->info('campaigns', 'campaigns', 'campaign_enrollment_skipped', [
                    'entity' => $enrollment,
                    'entity_type' => 'campaign_enrollment',
                    'entity_id' => $enrollment->id,
                    'outcome' => 'skipped',
                    'reason' => 'status_not_active',
                    'context' => $this->enrollmentContext($enrollment),
                ]);

                return;
            }

            $campaign = $enrollment->campaign;

            if (! $campaign || $campaign->status !== $campaign::STATUS_ACTIVE) {
                $enrollment->markExited('campaign_inactive');

                $this->logger->warning('campaigns', 'campaigns', 'campaign_enrollment_exited', [
                    'entity' => $enrollment,
                    'entity_type' => 'campaign_enrollment',
                    'entity_id' => $enrollment->id,
                    'outcome' => 'exited',
                    'reason' => 'campaign_inactive',
                    'context' => $this->enrollmentContext($enrollment),
                ]);

                return;
            }

            /** @var \App\Models\CampaignStep|null $currentStep */
            $currentStep = $campaign->steps
                ->where('is_enabled', true)
                ->sortBy('step_order')
                ->firstWhere('step_order', $enrollment->current_step_order);

            if (! $currentStep) {
                $enrollment->markCompleted('no_current_step');

                $this->logger->warning('campaigns', 'campaigns', 'campaign_enrollment_completed', [
                    'entity' => $enrollment,
                    'entity_type' => 'campaign_enrollment',
                    'entity_id' => $enrollment->id,
                    'outcome' => 'completed',
                    'reason' => 'no_current_step',
                    'context' => $this->enrollmentContext($enrollment),
                ]);

                return;
            }

            $this->stepSendService->sendStep($enrollment, $currentStep);
            $enrollment->refresh();

            if ($enrollment->status !== CampaignEnrollment::STATUS_ACTIVE) {
                $level = $enrollment->status === CampaignEnrollment::STATUS_FAILED ? 'error' : 'warning';
                $event = $enrollment->status === CampaignEnrollment::STATUS_FAILED
                    ? 'campaign_enrollment_failed'
                    : 'campaign_enrollment_exited';

                $this->logger->{$level}('campaigns', 'campaigns', $event, [
                    'entity' => $enrollment,
                    'entity_type' => 'campaign_enrollment',
                    'entity_id' => $enrollment->id,
                    'outcome' => $enrollment->status,
                    'reason' => $enrollment->exit_reason,
                    'context' => $this->enrollmentContext($enrollment, [
                        'current_step_order' => $currentStep->step_order,
                    ]),
                ]);

                return;
            }

            $nextStep = $campaign->steps
                ->where('is_enabled', true)
                ->sortBy('step_order')
                ->first(fn (CampaignStep $step) => $step->step_order > $currentStep->step_order);

            if (! $nextStep) {
                $enrollment->markCompleted('completed');

                $this->logger->info('campaigns', 'campaigns', 'campaign_enrollment_completed', [
                    'entity' => $enrollment,
                    'entity_type' => 'campaign_enrollment',
                    'entity_id' => $enrollment->id,
                    'outcome' => 'completed',
                    'reason' => 'completed',
                    'context' => $this->enrollmentContext($enrollment, [
                        'current_step_order' => $currentStep->step_order,
                    ]),
                ]);

                return;
            }

            $enrollment->forceFill([
                'current_step_order' => $nextStep->step_order,
                'next_run_at' => $this->calculateNextRunAt(
                    (int) $nextStep->delay_amount,
                    (string) $nextStep->delay_unit,
                    now(),
                ),
            ])->save();

            $this->logger->info('campaigns', 'campaigns', 'campaign_enrollment_advanced', [
                'entity' => $enrollment,
                'entity_type' => 'campaign_enrollment',
                'entity_id' => $enrollment->id,
                'outcome' => 'advanced',
                'context' => $this->enrollmentContext($enrollment, [
                    'current_step_order' => $currentStep->step_order,
                    'next_step_order' => $nextStep->step_order,
                ]),
            ]);
        });
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

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function enrollmentContext(CampaignEnrollment $enrollment, array $context = []): array
    {
        return array_filter([
            'campaign_id' => $enrollment->campaign_id,
            'campaign_status' => $enrollment->campaign?->status,
            'enrollment_status' => $enrollment->status,
            'current_step_order' => $enrollment->current_step_order,
            'next_run_at' => $enrollment->next_run_at?->toIso8601String(),
            'lead_id' => $enrollment->lead_id,
            'popup_lead_id' => $enrollment->popup_lead_id,
            'acquisition_contact_id' => $enrollment->acquisition_contact_id,
            ...$context,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
