<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Models\Lead;
use App\Models\PopupLead;
use App\Services\Campaigns\CampaignEnrollmentAdminService;
use App\Services\Logging\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignEnrollmentController extends Controller
{
    public function __construct(
        private readonly CampaignEnrollmentAdminService $campaignEnrollmentAdminService,
        private readonly AdminActivityLogger $adminLogger,
    ) {}

    public function index(Request $request): Response
    {
        $campaignId = max(0, (int) $request->integer('campaign'));
        $status = trim((string) $request->string('status')->toString());

        $enrollments = CampaignEnrollment::query()
            ->with([
                'campaign:id,name,status,entry_trigger',
                'campaign.steps' => fn ($query) => $query->enabled()->orderBy('step_order'),
                'lead:id,first_name,email',
                'popupLead:id,name,email',
                'acquisitionContact:id,display_name,primary_email',
            ])
            ->when($campaignId > 0, fn ($query) => $query->where('campaign_id', $campaignId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CampaignEnrollment $enrollment): array => [
                'id' => $enrollment->id,
                'campaign' => [
                    'id' => $enrollment->campaign?->id,
                    'name' => $enrollment->campaign?->name ?? 'Unknown campaign',
                ],
                'recipient' => $this->recipientFor($enrollment),
                'source' => $this->sourceFor($enrollment),
                'current_step' => $this->currentStepFor($enrollment),
                'status' => $enrollment->status,
                'status_label' => $this->humanize($enrollment->status),
                'next_run_at' => optional($enrollment->next_run_at)?->toISOString(),
                'started_at' => optional($enrollment->started_at)?->toISOString(),
                'completed_at' => optional($enrollment->completed_at)?->toISOString(),
                'exit_reason' => $this->exitReasonLabel($enrollment->exit_reason),
            ]);

        return Inertia::render('Admin/CampaignEnrollments/Index', [
            'filters' => [
                'campaign' => $campaignId > 0 ? (string) $campaignId : '',
                'status' => $status,
            ],
            'campaignOptions' => Campaign::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Campaign $campaign): array => [
                    'value' => (string) $campaign->id,
                    'label' => $campaign->name,
                ])
                ->values(),
            'statusOptions' => [
                ['value' => CampaignEnrollment::STATUS_ACTIVE, 'label' => 'Active'],
                ['value' => CampaignEnrollment::STATUS_PAUSED, 'label' => 'Paused'],
                ['value' => CampaignEnrollment::STATUS_COMPLETED, 'label' => 'Completed'],
                ['value' => CampaignEnrollment::STATUS_EXITED, 'label' => 'Exited'],
                ['value' => CampaignEnrollment::STATUS_FAILED, 'label' => 'Failed'],
            ],
            'enrollments' => $enrollments,
        ]);
    }

    public function show(CampaignEnrollment $campaignEnrollment): Response
    {
        $campaignEnrollment->load([
            'campaign:id,name,status,entry_trigger',
            'campaign.steps' => fn ($query) => $query->enabled()->orderBy('step_order'),
            'lead:id,first_name,email',
            'popupLead:id,name,email,popup_id',
            'acquisitionContact:id,display_name,primary_email',
        ]);

        return Inertia::render('Admin/CampaignEnrollments/Show', [
            'enrollment' => [
                'id' => $campaignEnrollment->id,
                'campaign' => [
                    'id' => $campaignEnrollment->campaign?->id,
                    'name' => $campaignEnrollment->campaign?->name ?? 'Unknown campaign',
                    'status' => $campaignEnrollment->campaign?->status ?? 'unknown',
                    'status_label' => $this->humanize((string) ($campaignEnrollment->campaign?->status ?? 'unknown')),
                    'entry_trigger' => $campaignEnrollment->campaign?->entry_trigger ?? 'unknown',
                    'entry_trigger_label' => $this->entryTriggerLabel((string) ($campaignEnrollment->campaign?->entry_trigger ?? 'unknown')),
                ],
                'recipient' => $this->recipientFor($campaignEnrollment),
                'source' => $this->sourceFor($campaignEnrollment),
                'current_step' => $this->currentStepFor($campaignEnrollment),
                'status' => $campaignEnrollment->status,
                'status_label' => $this->humanize($campaignEnrollment->status),
                'next_run_at' => optional($campaignEnrollment->next_run_at)?->toISOString(),
                'started_at' => optional($campaignEnrollment->started_at)?->toISOString(),
                'completed_at' => optional($campaignEnrollment->completed_at)?->toISOString(),
                'exit_reason' => $this->exitReasonLabel($campaignEnrollment->exit_reason),
                'can_pause' => $campaignEnrollment->status === CampaignEnrollment::STATUS_ACTIVE,
                'can_resume' => $campaignEnrollment->status === CampaignEnrollment::STATUS_PAUSED,
                'can_exit' => in_array($campaignEnrollment->status, [
                    CampaignEnrollment::STATUS_ACTIVE,
                    CampaignEnrollment::STATUS_PAUSED,
                    CampaignEnrollment::STATUS_FAILED,
                ], true),
            ],
        ]);
    }

    public function pause(CampaignEnrollment $campaignEnrollment): RedirectResponse
    {
        $previousStatus = $campaignEnrollment->status;

        $this->campaignEnrollmentAdminService->pause($campaignEnrollment);

        $campaignEnrollment->refresh();

        $this->adminLogger->info(
            event: 'campaign_enrollment_paused',
            request: request(),
            entity: $campaignEnrollment,
            entityType: 'campaign_enrollment',
            entityId: $campaignEnrollment->id,
            outcome: 'paused',
            context: $this->enrollmentLogContext($campaignEnrollment, [
                'enrollment_id' => $campaignEnrollment->id,
                'previous_status' => $previousStatus,
                'new_status' => $campaignEnrollment->status,
            ]),
        );

        return back()->with('success', 'Campaign enrollment paused.');
    }

    public function resume(CampaignEnrollment $campaignEnrollment): RedirectResponse
    {
        $previousStatus = $campaignEnrollment->status;

        $this->campaignEnrollmentAdminService->resume($campaignEnrollment);

        $campaignEnrollment->refresh();

        $this->adminLogger->info(
            event: 'campaign_enrollment_resumed',
            request: request(),
            entity: $campaignEnrollment,
            entityType: 'campaign_enrollment',
            entityId: $campaignEnrollment->id,
            outcome: 'resumed',
            context: $this->enrollmentLogContext($campaignEnrollment, [
                'enrollment_id' => $campaignEnrollment->id,
                'previous_status' => $previousStatus,
                'new_status' => $campaignEnrollment->status,
            ]),
        );

        return back()->with('success', 'Campaign enrollment resumed.');
    }

    public function exit(CampaignEnrollment $campaignEnrollment): RedirectResponse
    {
        $previousStatus = $campaignEnrollment->status;

        $this->campaignEnrollmentAdminService->exit($campaignEnrollment);

        $campaignEnrollment->refresh();

        $this->adminLogger->info(
            event: 'campaign_enrollment_exited',
            request: request(),
            entity: $campaignEnrollment,
            entityType: 'campaign_enrollment',
            entityId: $campaignEnrollment->id,
            outcome: 'exited',
            reason: $campaignEnrollment->exit_reason,
            context: $this->enrollmentLogContext($campaignEnrollment, [
                'enrollment_id' => $campaignEnrollment->id,
                'previous_status' => $previousStatus,
                'new_status' => $campaignEnrollment->status,
            ]),
        );

        return back()->with('success', 'Campaign enrollment exited.');
    }

    /**
     * @return array{name: string, email: string|null}
     */
    private function recipientFor(CampaignEnrollment $enrollment): array
    {
        if ($enrollment->lead instanceof Lead) {
            return [
                'name' => $enrollment->lead->first_name ?: 'Lead #'.$enrollment->lead->id,
                'email' => $enrollment->lead->email,
            ];
        }

        if ($enrollment->popupLead instanceof PopupLead) {
            return [
                'name' => $enrollment->popupLead->name ?: 'Popup Lead #'.$enrollment->popupLead->id,
                'email' => $enrollment->popupLead->email,
            ];
        }

        return [
            'name' => $enrollment->acquisitionContact?->display_name
                ?: 'Acquisition Contact #'.($enrollment->acquisition_contact_id ?? $enrollment->id),
            'email' => $enrollment->acquisitionContact?->primary_email,
        ];
    }

    /**
     * @return array{label: string, identity: string, route: string|null}
     */
    private function sourceFor(CampaignEnrollment $enrollment): array
    {
        if ($enrollment->lead instanceof Lead) {
            return [
                'label' => 'Lead',
                'identity' => 'Lead #'.$enrollment->lead->id,
                'route' => null,
            ];
        }

        if ($enrollment->popupLead instanceof PopupLead) {
            return [
                'label' => 'Popup Lead',
                'identity' => 'Popup Lead #'.$enrollment->popupLead->id,
                'route' => null,
            ];
        }

        return [
            'label' => 'Acquisition Contact',
            'identity' => 'Acquisition Contact #'.($enrollment->acquisitionContact?->id ?? $enrollment->acquisition_contact_id ?? $enrollment->id),
            'route' => $enrollment->acquisitionContact
                ? route('admin.acquisition.contacts.show', $enrollment->acquisitionContact)
                : null,
        ];
    }

    /**
     * @return array{order: int|null, label: string, send_mode: string|null, delay: string|null}
     */
    private function currentStepFor(CampaignEnrollment $enrollment): array
    {
        $step = $enrollment->campaign?->steps
            ?->firstWhere('step_order', $enrollment->current_step_order);

        if (! $step instanceof CampaignStep) {
            return [
                'order' => null,
                'label' => 'Unavailable',
                'send_mode' => null,
                'delay' => null,
            ];
        }

        return [
            'order' => $step->step_order,
            'label' => 'Step '.$step->step_order,
            'send_mode' => $this->humanize($step->send_mode),
            'delay' => $step->delay_amount.' '.$this->humanize($step->delay_unit),
        ];
    }

    private function entryTriggerLabel(string $entryTrigger): string
    {
        $definition = collect(config('communication-bindings', []))
            ->firstWhere('event_key', $entryTrigger);

        return (string) ($definition['label'] ?? $entryTrigger);
    }

    private function exitReasonLabel(?string $reason): string
    {
        if (blank($reason)) {
            return '—';
        }

        if ($reason === 'Manually exited by admin') {
            return $reason;
        }

        return $this->humanize($reason);
    }

    private function humanize(string $value): string
    {
        return str($value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentLogContext(CampaignEnrollment $enrollment, array $context = []): array
    {
        return array_filter([
            'enrollment_id' => $enrollment->id,
            'campaign_id' => $enrollment->campaign_id,
            'lead_id' => $enrollment->lead_id,
            'popup_lead_id' => $enrollment->popup_lead_id,
            'acquisition_contact_id' => $enrollment->acquisition_contact_id,
            'status' => $enrollment->status,
            'current_step_order' => $enrollment->current_step_order,
            'next_run_at' => $enrollment->next_run_at?->toIso8601String(),
            ...$context,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
