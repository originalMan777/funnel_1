<?php

namespace App\Services\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Services\Logging\StructuredEventLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CampaignEnrollmentService
{
    public function __construct(
        private readonly StructuredEventLogger $logger,
    ) {}

    public function enrollForTrigger(string $trigger, array $context = []): void
    {
        $campaigns = Campaign::query()
            ->active()
            ->forTrigger($trigger)
            ->with(['steps' => fn ($query) => $query->enabled()->orderBy('step_order')])
            ->get();

        $this->logger->info('campaigns', 'campaigns', 'campaign_trigger_received', [
            'entity_type' => 'campaign_trigger',
            'entity_id' => $trigger,
            'outcome' => 'received',
            'context' => [
                'event_key' => $trigger,
                'matched_campaign_count' => $campaigns->count(),
                'lead_id' => $context['lead_id'] ?? null,
                'popup_lead_id' => $context['popup_lead_id'] ?? null,
                'acquisition_contact_id' => $context['acquisition_contact_id'] ?? null,
            ],
        ]);

        foreach ($campaigns as $campaign) {
            $this->createEnrollment($campaign, $context);
        }
    }

    public function createEnrollment(Campaign $campaign, array $context = []): CampaignEnrollment
    {
        return DB::transaction(function () use ($campaign, $context): CampaignEnrollment {
            $firstStep = $campaign->steps
                ->where('is_enabled', true)
                ->sortBy('step_order')
                ->first();

            $startedAt = now();

            /** @var \App\Models\CampaignEnrollment $enrollment */
            $enrollment = CampaignEnrollment::query()->create([
                'campaign_id' => $campaign->id,
                'lead_id' => $context['lead_id'] ?? null,
                'popup_lead_id' => $context['popup_lead_id'] ?? null,
                'acquisition_contact_id' => $context['acquisition_contact_id'] ?? null,
                'current_step_order' => $firstStep?->step_order ?? 1,
                'status' => CampaignEnrollment::STATUS_ACTIVE,
                'next_run_at' => $firstStep
                    ? $this->calculateNextRunAt(
                        (int) $firstStep->delay_amount,
                        (string) $firstStep->delay_unit,
                        $startedAt,
                    )
                    : null,
                'started_at' => $startedAt,
                'completed_at' => null,
                'exit_reason' => null,
            ]);

            $this->logger->info('campaigns', 'campaigns', 'campaign_enrollment_created', [
                'entity' => $enrollment,
                'entity_type' => 'campaign_enrollment',
                'entity_id' => $enrollment->id,
                'outcome' => 'created',
                'context' => $this->enrollmentContext($campaign, $enrollment, $context),
            ]);

            if ($firstStep === null) {
                $enrollment->markCompleted('no_steps');

                $this->logger->info('campaigns', 'campaigns', 'campaign_enrollment_completed_without_steps', [
                    'entity' => $enrollment,
                    'entity_type' => 'campaign_enrollment',
                    'entity_id' => $enrollment->id,
                    'outcome' => 'completed',
                    'reason' => 'no_steps',
                    'context' => $this->enrollmentContext($campaign, $enrollment, $context),
                ]);
            }

            return $enrollment;
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
    private function enrollmentContext(Campaign $campaign, CampaignEnrollment $enrollment, array $context = []): array
    {
        return array_filter([
            'campaign_id' => $campaign->id,
            'campaign_status' => $campaign->status,
            'entry_trigger' => $campaign->entry_trigger,
            'enrollment_status' => $enrollment->status,
            'current_step_order' => $enrollment->current_step_order,
            'next_run_at' => $enrollment->next_run_at?->toIso8601String(),
            'lead_id' => $context['lead_id'] ?? $enrollment->lead_id,
            'popup_lead_id' => $context['popup_lead_id'] ?? $enrollment->popup_lead_id,
            'acquisition_contact_id' => $context['acquisition_contact_id'] ?? $enrollment->acquisition_contact_id,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
