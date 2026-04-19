<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\Admin\Campaigns\StoreCampaignRequest;
use App\Http\Requests\Requests\Admin\Campaigns\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\CommunicationTemplate;
use App\Services\Campaigns\CampaignService;
use App\Services\Logging\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
        private readonly AdminActivityLogger $adminLogger,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Campaigns/Index', [
            'campaigns' => Campaign::query()
                ->withCount(['steps', 'enrollments'])
                ->latest('id')
                ->get()
                ->map(fn (Campaign $campaign): array => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'audience_type' => $campaign->audience_type,
                    'entry_trigger' => $campaign->entry_trigger,
                    'entry_trigger_label' => $this->entryTriggerLabelFor($campaign->entry_trigger),
                    'description' => $campaign->description,
                    'steps_count' => $campaign->steps_count,
                    'enrollments_count' => $campaign->enrollments_count,
                    'updated_at' => optional($campaign->updated_at)?->toDateTimeString(),
                ])
                ->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Campaigns/Create', [
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $campaign = $this->campaignService->create(
            $request->validatedCampaignData(),
            $request->validatedSteps(),
            auth()->id(),
        );

        $this->adminLogger->info(
            event: 'campaign_created',
            request: $request,
            entity: $campaign,
            entityType: 'campaign',
            entityId: $campaign->id,
            outcome: 'created',
            context: $this->campaignLogContext($campaign),
        );

        return to_route('admin.campaigns.edit', $campaign)
            ->with('success', 'Campaign created.');
    }

    public function edit(Campaign $campaign): Response
    {
        $campaign->load([
            'steps.template.currentVersion',
        ]);

        return Inertia::render('Admin/Campaigns/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'audience_type' => $campaign->audience_type,
                'entry_trigger' => $campaign->entry_trigger,
                'description' => $campaign->description,
                'steps' => $campaign->steps
                    ->sortBy('step_order')
                    ->values()
                    ->map(fn ($step): array => [
                        'id' => $step->id,
                        'step_order' => $step->step_order,
                        'delay_amount' => $step->delay_amount,
                        'delay_unit' => $step->delay_unit,
                        'send_mode' => $step->send_mode,
                        'template_id' => $step->template_id,
                        'subject' => $step->subject,
                        'html_body' => $step->html_body,
                        'text_body' => $step->text_body,
                        'is_enabled' => (bool) $step->is_enabled,
                    ])
                    ->all(),
            ],
            'formOptions' => $this->formOptions($campaign),
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $campaign->loadMissing('steps');

        $originalSnapshot = $this->campaignSnapshot($campaign);
        $previousStatus = $campaign->status;

        $campaignData = $request->validatedCampaignData();
        $steps = $request->validatedSteps();

        $this->campaignService->update(
            $campaign,
            $campaignData,
            $steps,
            auth()->id(),
        );

        $campaign->refresh()->loadMissing('steps');

        $changedFields = $this->changedCampaignFields($originalSnapshot, $this->campaignSnapshot($campaign));

        $nonStatusChangedFields = array_values(array_filter(
            $changedFields,
            static fn (string $field): bool => $field !== 'status'
        ));

        if ($nonStatusChangedFields !== []) {
            $this->adminLogger->info(
                event: 'campaign_updated',
                request: $request,
                entity: $campaign,
                entityType: 'campaign',
                entityId: $campaign->id,
                outcome: 'updated',
                context: $this->campaignLogContext($campaign, [
                    'changed_fields' => $nonStatusChangedFields,
                ]),
            );
        }

        if ($previousStatus !== $campaign->status) {
            $this->adminLogger->info(
                event: 'campaign_status_changed',
                request: $request,
                entity: $campaign,
                entityType: 'campaign',
                entityId: $campaign->id,
                outcome: 'updated',
                context: $this->campaignLogContext($campaign, [
                    'previous_status' => $previousStatus,
                    'new_status' => $campaign->status,
                ]),
            );
        }

        return to_route('admin.campaigns.edit', $campaign)
            ->with('success', 'Campaign updated.');
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function formOptions(?Campaign $campaign = null): array
    {
        $selectedTemplateIds = $campaign?->steps()
            ->pluck('template_id')
            ->filter()
            ->values()
            ->all() ?? [];

        $templates = CommunicationTemplate::query()
            ->with('currentVersion')
            ->where(function ($query) use ($selectedTemplateIds): void {
                $query->where(function ($innerQuery): void {
                    $innerQuery->active()
                        ->whereNotNull('current_version_id');
                });

                if ($selectedTemplateIds !== []) {
                    $query->orWhereIn('id', $selectedTemplateIds);
                }
            })
            ->orderBy('name')
            ->get()
            ->filter(fn (CommunicationTemplate $template): bool => $template->id !== null
                && ($template->hasPublishedCurrentVersion() || in_array($template->id, $selectedTemplateIds, true)))
            ->map(fn (CommunicationTemplate $template): array => [
                'value' => $template->id,
                'label' => $template->name,
                'description' => collect([
                    $template->key,
                    $template->currentVersion ? 'v'.$template->currentVersion->version_number : null,
                    $template->hasPublishedCurrentVersion() ? 'published' : $template->status,
                ])->filter()->implode(' - '),
            ])
            ->values()
            ->all();

        return [
            'statusOptions' => [
                ['value' => Campaign::STATUS_DRAFT, 'label' => 'Draft'],
                ['value' => Campaign::STATUS_ACTIVE, 'label' => 'Active'],
                ['value' => Campaign::STATUS_PAUSED, 'label' => 'Paused'],
                ['value' => Campaign::STATUS_ARCHIVED, 'label' => 'Archived'],
            ],
            'audienceOptions' => [
                ['value' => Campaign::AUDIENCE_LEADS, 'label' => 'Leads'],
                ['value' => Campaign::AUDIENCE_POPUP_LEADS, 'label' => 'Popup Leads'],
                ['value' => Campaign::AUDIENCE_ACQUISITION_CONTACTS, 'label' => 'Acquisition Contacts'],
            ],
            'entryTriggerOptions' => collect(config('communication-bindings', []))
                ->map(fn (array $definition): array => [
                    'value' => (string) ($definition['event_key'] ?? ''),
                    'label' => (string) ($definition['label'] ?? $definition['event_key'] ?? ''),
                ])
                ->filter(fn (array $option): bool => filled($option['value']))
                ->values()
                ->all(),
            'templateOptions' => $templates,
            'delayUnitOptions' => [
                ['value' => 'days', 'label' => 'Days'],
                ['value' => 'hours', 'label' => 'Hours'],
                ['value' => 'weeks', 'label' => 'Weeks'],
            ],
            'sendModeOptions' => [
                ['value' => 'template', 'label' => 'Use template'],
                ['value' => 'custom', 'label' => 'Custom message'],
            ],
        ];
    }

    private function entryTriggerLabelFor(string $entryTrigger): string
    {
        $definition = collect(config('communication-bindings', []))
            ->firstWhere('event_key', $entryTrigger);

        return (string) ($definition['label'] ?? $entryTrigger);
    }

    /**
     * @return array<string, mixed>
     */
    private function campaignLogContext(Campaign $campaign, array $context = []): array
    {
        return [
            'campaign_id' => $campaign->id,
            'campaign_status' => $campaign->status,
            'audience_type' => $campaign->audience_type,
            'entry_trigger' => $campaign->entry_trigger,
            'steps_count' => $campaign->steps()->count(),
            ...$context,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function campaignSnapshot(Campaign $campaign): array
    {
        return [
            'name' => $campaign->name,
            'status' => $campaign->status,
            'audience_type' => $campaign->audience_type,
            'entry_trigger' => $campaign->entry_trigger,
            'description' => $campaign->description,
            'steps' => $campaign->steps
                ->sortBy('step_order')
                ->values()
                ->map(fn ($step): array => [
                    'step_order' => (int) $step->step_order,
                    'delay_amount' => (int) $step->delay_amount,
                    'delay_unit' => (string) $step->delay_unit,
                    'send_mode' => (string) $step->send_mode,
                    'template_id' => $step->template_id,
                    'subject' => $step->subject,
                    'html_body' => $step->html_body,
                    'text_body' => $step->text_body,
                    'is_enabled' => (bool) $step->is_enabled,
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<int, string>
     */
    private function changedCampaignFields(array $before, array $after): array
    {
        return collect(array_keys($after))
            ->filter(fn (string $field): bool => ($before[$field] ?? null) !== ($after[$field] ?? null))
            ->values()
            ->all();
    }
}
