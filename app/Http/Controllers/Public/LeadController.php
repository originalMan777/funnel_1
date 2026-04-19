<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcquisitionEvent;
use App\Models\AcquisitionSource;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Services\Acquisition\AcquisitionContactResolver;
use App\Services\Communications\CommunicationService;
use App\Services\Leads\LeadAcquisitionResolver;
use App\Services\Logging\StructuredEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function __construct(
        private readonly AcquisitionContactResolver $acquisitionContactResolver,
        private readonly LeadAcquisitionResolver $leadAcquisitionResolver,
        private readonly CommunicationService $communicationService,
        private readonly StructuredEventLogger $logger,
    ) {}

    private function requireLeadBoxAccess(Request $request): void
    {
        if (! $request->user()?->canManageLeadBoxes()) {
            abort(403);
        }
    }

    public function index(Request $request): Response
    {
        $this->requireLeadBoxAccess($request);

        $slots = collect($this->slotDefinitions())
            ->map(function (array $definition, string $slotKey) {
                $slot = LeadSlot::query()->firstOrCreate(
                    ['key' => $slotKey],
                    ['is_enabled' => true],
                );

                return [
                    'id' => $slot->id,
                    'key' => $slot->key,
                    'label' => $definition['label'],
                    'is_enabled' => $slot->is_enabled,
                    'required_type' => $definition['required_type'],
                    'assignment_lead_box_id' => optional($slot->assignment)?->lead_box_id,
                ];
            })
            ->values();

        $activeResourceBoxes = LeadBox::query()
            ->where('type', LeadBox::TYPE_RESOURCE)
            ->where('status', LeadBox::STATUS_ACTIVE)
            ->orderBy('internal_name')
            ->get()
            ->map(fn (LeadBox $box) => [
                'id' => $box->id,
                'internal_name' => $box->internal_name,
                'title' => $box->title,
            ]);

        $activeServiceBoxes = LeadBox::query()
            ->where('type', LeadBox::TYPE_SERVICE)
            ->where('status', LeadBox::STATUS_ACTIVE)
            ->orderBy('internal_name')
            ->get()
            ->map(fn (LeadBox $box) => [
                'id' => $box->id,
                'internal_name' => $box->internal_name,
                'title' => $box->title,
            ]);

        $activeOfferBoxes = LeadBox::query()
            ->where('type', LeadBox::TYPE_OFFER)
            ->where('status', LeadBox::STATUS_ACTIVE)
            ->orderBy('internal_name')
            ->get()
            ->map(fn (LeadBox $box) => [
                'id' => $box->id,
                'internal_name' => $box->internal_name,
                'title' => $box->title,
            ]);

        return Inertia::render('Admin/LeadSlots/Index', [
            'slots' => $slots,
            'activeResourceBoxes' => $activeResourceBoxes,
            'activeServiceBoxes' => $activeServiceBoxes,
            'activeOfferBoxes' => $activeOfferBoxes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_box_id' => ['required', 'integer', Rule::exists('lead_boxes', 'id')],
            'lead_slot_key' => ['required', 'string', Rule::in(array_keys($this->slotDefinitions()))],
            'first_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'message' => ['nullable', 'string'],
            'page_key' => ['nullable', 'string', 'max:255'],
            'acquisition_path_key' => ['nullable', 'string', 'max:255'],
            'acquisition_slug' => ['nullable', 'string', 'max:255'],
            'service_slug' => ['nullable', 'string', 'max:255'],
            'source_popup_key' => ['nullable', 'string', 'max:255'],
        ]);

        $slot = LeadSlot::query()
            ->with('assignment')
            ->where('key', $validated['lead_slot_key'])
            ->first();

        if (! $slot || ! $slot->is_enabled) {
            return redirect()
                ->back()
                ->withErrors([
                    'lead_slot_key' => 'This lead slot is not available.',
                ])
                ->withInput();
        }

        $leadBox = LeadBox::query()->findOrFail((int) $validated['lead_box_id']);

        if ($leadBox->status !== LeadBox::STATUS_ACTIVE) {
            return redirect()
                ->back()
                ->withErrors([
                    'lead_box_id' => 'This lead box is not available for that slot.',
                ])
                ->withInput();
        }

        if (! $slot->assignment || (int) $slot->assignment->lead_box_id !== (int) $leadBox->id) {
            return redirect()
                ->back()
                ->withErrors([
                    'lead_box_id' => 'This lead box is not assigned to that slot.',
                ])
                ->withInput();
        }

        if ($leadBox->type === LeadBox::TYPE_SERVICE && blank($validated['phone'] ?? null)) {
            return redirect()
                ->back()
                ->withErrors([
                    'phone' => 'Phone is required for this lead type.',
                ])
                ->withInput();
        }

        $pageKey = $validated['page_key'] ?? ($this->slotDefinitions()[$slot->key]['page_key'] ?? null);

        $assignment = $slot->assignment;
        $acquisitionContext = $this->leadAcquisitionResolver->resolveForLeadRequest(
            request: $request,
            assignment: $assignment,
            fallbackPageKey: $pageKey,
        );

        $this->logger->info('leads', 'leads', 'lead_submission_accepted', [
            'request' => $request,
            'entity_type' => 'lead_submission',
            'entity_id' => null,
            'outcome' => 'accepted',
            'context' => [
                'lead_type' => $leadBox->type,
                'page_key' => $pageKey,
                'lead_box_id' => $leadBox->id,
                'lead_slot_key' => $slot->key,
                'acquisition_id' => $acquisitionContext['acquisitionId'],
                'service_id' => $acquisitionContext['serviceId'],
                'acquisition_path_id' => $acquisitionContext['acquisitionPathId'],
                'acquisition_path_key' => $acquisitionContext['acquisitionPathKey'],
                'source_popup_key' => $acquisitionContext['sourcePopupKey'],
            ],
        ]);

        $lead = DB::transaction(function () use ($acquisitionContext, $leadBox, $pageKey, $slot, $validated): Lead {
            $lead = Lead::query()->create([
                'lead_box_id' => $leadBox->id,
                'lead_slot_key' => $slot->key,
                'page_key' => $pageKey,
                'acquisition_id' => $acquisitionContext['acquisitionId'],
                'service_id' => $acquisitionContext['serviceId'],
                'acquisition_path_id' => $acquisitionContext['acquisitionPathId'],
                'acquisition_path_key' => $acquisitionContext['acquisitionPathKey'],
                'source_page_key' => $acquisitionContext['sourcePageKey'],
                'source_slot_key' => $acquisitionContext['sourceSlotKey'],
                'source_popup_key' => $acquisitionContext['sourcePopupKey'],
                'type' => $leadBox->type,
                'first_name' => $validated['first_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'source_url' => $validated['source_url'] ?? null,
                'entry_url' => $validated['source_url'] ?? null,
                'lead_status' => 'new',
                'payload' => [
                    'phone' => $validated['phone'] ?? null,
                    'message' => $validated['message'] ?? null,
                ],
            ]);

            $this->attachLeadToAcquisition($lead);

            return $lead;
        });

        $this->logger->info('leads', 'leads', 'lead_persisted', [
            'request' => $request,
            'entity' => $lead,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'outcome' => 'created',
            'context' => $this->leadLogContext($lead),
        ]);

        $this->communicationService->recordAndQueue(
            eventKey: 'lead.created',
            subject: $lead,
            acquisitionContactId: $lead->acquisition_contact_id,
            payload: [
                'lead_id' => $lead->id,
                'lead_type' => $lead->type,
                'lead_box_id' => $lead->lead_box_id,
                'lead_slot_key' => $lead->lead_slot_key,
                'page_key' => $lead->page_key,
                'email' => $lead->email,
            ],
        );

        return redirect(route('home'))
            ->cookie('nojo_lead_captured', '1', 60 * 24 * 30)
            ->with('success', 'Thanks. We received your request.');
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string'],
            'page_key' => ['nullable', 'string', 'max:255'],
            'lead_slot_key' => ['nullable', 'string', 'max:255'],
            'source_popup_key' => ['nullable', 'string', 'max:255'],
            'acquisition_path_key' => ['nullable', 'string', 'max:255'],
            'acquisition_slug' => ['nullable', 'string', 'max:255'],
            'service_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $assignment = $this->resolveAssignmentForRequest($request);
        $acquisitionContext = $this->leadAcquisitionResolver->resolveForLeadRequest(
            request: $request,
            assignment: $assignment,
            fallbackPageKey: 'contact',
        );

        $this->logger->info('leads', 'leads', 'lead_submission_accepted', [
            'request' => $request,
            'entity_type' => 'lead_submission',
            'outcome' => 'accepted',
            'context' => [
                'lead_type' => 'contact',
                'page_key' => 'contact',
                'lead_slot_key' => $assignment?->leadSlot?->key,
                'lead_box_id' => $assignment?->lead_box_id,
                'acquisition_id' => $acquisitionContext['acquisitionId'],
                'service_id' => $acquisitionContext['serviceId'],
                'acquisition_path_id' => $acquisitionContext['acquisitionPathId'],
                'acquisition_path_key' => $acquisitionContext['acquisitionPathKey'],
                'source_popup_key' => $acquisitionContext['sourcePopupKey'],
            ],
        ]);

        $lead = $this->storePublicLead(
            firstName: $validated['name'],
            email: $validated['email'],
            type: 'contact',
            submissionPageKey: 'contact',
            sourceUrl: $this->resolvePublicSourceUrl($request, route('contact')),
            acquisitionContext: $acquisitionContext,
            payload: [
                'message' => $validated['message'],
            ],
        );

        $this->logger->info('leads', 'leads', 'lead_persisted', [
            'request' => $request,
            'entity' => $lead,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'outcome' => 'created',
            'context' => $this->leadLogContext($lead),
        ]);

        $this->communicationService->recordAndQueue(
            eventKey: 'contact.requested',
            subject: $lead,
            acquisitionContactId: $lead->acquisition_contact_id,
            payload: [
                'lead_id' => $lead->id,
                'page_key' => $lead->page_key,
                'email' => $lead->email,
                'message' => $lead->payload['message'] ?? null,
            ],
        );

        return redirect()->back()->with('success', 'Thanks. We received your request.');
    }

    public function storeConsultation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string'],
            'page_key' => ['nullable', 'string', 'max:255'],
            'lead_slot_key' => ['nullable', 'string', 'max:255'],
            'source_popup_key' => ['nullable', 'string', 'max:255'],
            'acquisition_path_key' => ['nullable', 'string', 'max:255'],
            'acquisition_slug' => ['nullable', 'string', 'max:255'],
            'service_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $assignment = $this->resolveAssignmentForRequest($request);
        $acquisitionContext = $this->leadAcquisitionResolver->resolveForLeadRequest(
            request: $request,
            assignment: $assignment,
            fallbackPageKey: 'consultation_request',
        );

        $this->logger->info('leads', 'leads', 'lead_submission_accepted', [
            'request' => $request,
            'entity_type' => 'lead_submission',
            'outcome' => 'accepted',
            'context' => [
                'lead_type' => 'consultation',
                'page_key' => 'consultation_request',
                'lead_slot_key' => $assignment?->leadSlot?->key,
                'lead_box_id' => $assignment?->lead_box_id,
                'acquisition_id' => $acquisitionContext['acquisitionId'],
                'service_id' => $acquisitionContext['serviceId'],
                'acquisition_path_id' => $acquisitionContext['acquisitionPathId'],
                'acquisition_path_key' => $acquisitionContext['acquisitionPathKey'],
                'source_popup_key' => $acquisitionContext['sourcePopupKey'],
            ],
        ]);

        $lead = $this->storePublicLead(
            firstName: $validated['name'],
            email: $validated['email'],
            type: 'consultation',
            submissionPageKey: 'consultation_request',
            sourceUrl: $this->resolvePublicSourceUrl($request, route('consultation.request')),
            acquisitionContext: $acquisitionContext,
            payload: [
                'phone' => $validated['phone'],
                'details' => $validated['details'],
            ],
        );

        $this->logger->info('leads', 'leads', 'lead_persisted', [
            'request' => $request,
            'entity' => $lead,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'outcome' => 'created',
            'context' => $this->leadLogContext($lead),
        ]);

        $this->communicationService->recordAndQueue(
            eventKey: 'lead.consultation_requested',
            subject: $lead,
            acquisitionContactId: $lead->acquisition_contact_id,
            payload: [
                'lead_id' => $lead->id,
                'page_key' => $lead->page_key,
                'email' => $lead->email,
                'phone' => $lead->payload['phone'] ?? null,
            ],
        );

        return redirect()->back()->with('success', 'Thanks. We received your request.');
    }

    public function update(Request $request, LeadSlot $leadSlot): RedirectResponse
    {
        $this->requireLeadBoxAccess($request);

        abort_unless(array_key_exists($leadSlot->key, $this->slotDefinitions()), 404);

        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'lead_box_id' => ['nullable', 'integer', Rule::exists('lead_boxes', 'id')],
        ]);

        $leadSlot->update([
            'is_enabled' => (bool) $validated['is_enabled'],
        ]);

        $leadBoxId = $validated['lead_box_id'] ?? null;

        if ($leadBoxId === null) {
            LeadAssignment::query()->where('lead_slot_id', $leadSlot->id)->delete();

            return redirect()
                ->back()
                ->with('success', 'Slot updated.');
        }

        $leadBox = LeadBox::query()->findOrFail((int) $leadBoxId);

        if ($leadBox->status !== LeadBox::STATUS_ACTIVE) {
            return redirect()
                ->back()
                ->withErrors([
                    'lead_box_id' => 'Only Active Lead Boxes can be assigned to this slot.',
                ]);
        }

        LeadAssignment::query()->updateOrCreate(
            ['lead_slot_id' => $leadSlot->id],
            ['lead_box_id' => $leadBox->id],
        );

        return redirect()
            ->back()
            ->with('success', 'Slot updated.');
    }

    /**
     * @return array<string, array{label:string,required_type:string,page_key:string}>
     */
    private function slotDefinitions(): array
    {
        return config('lead_blocks.slot_definitions', []);
    }

    private function storePublicLead(
        string $firstName,
        string $email,
        string $type,
        string $submissionPageKey,
        string $sourceUrl,
        array $acquisitionContext,
        array $payload
    ): Lead {
        return DB::transaction(function () use ($acquisitionContext, $email, $firstName, $payload, $sourceUrl, $submissionPageKey, $type): Lead {
            $lead = Lead::query()->create([
                'lead_box_id' => null,
                'lead_slot_key' => null,
                'page_key' => $submissionPageKey,
                'acquisition_id' => $acquisitionContext['acquisitionId'],
                'service_id' => $acquisitionContext['serviceId'],
                'acquisition_path_id' => $acquisitionContext['acquisitionPathId'],
                'acquisition_path_key' => $acquisitionContext['acquisitionPathKey'],
                'source_page_key' => $acquisitionContext['sourcePageKey'],
                'source_slot_key' => $acquisitionContext['sourceSlotKey'],
                'source_popup_key' => $acquisitionContext['sourcePopupKey'],
                'source_url' => $sourceUrl,
                'entry_url' => $sourceUrl,
                'lead_status' => 'new',
                'type' => $type,
                'first_name' => trim($firstName),
                'email' => $email,
                'payload' => $payload,
            ]);

            $this->attachLeadToAcquisition($lead);

            return $lead;
        });
    }

    private function resolvePublicSourceUrl(Request $request, string $fallback): string
    {
        $sourceUrl = (string) ($request->headers->get('referer') ?? '');

        return $sourceUrl !== '' ? $sourceUrl : $fallback;
    }

    private function attachLeadToAcquisition(Lead $lead): void
    {
        $contact = $this->acquisitionContactResolver->resolveFromLead($lead);

        $lead->forceFill([
            'acquisition_contact_id' => $contact->id,
        ])->save();

        AcquisitionSource::query()->create([
            'acquisition_contact_id' => $contact->id,
            'source_type' => 'lead_submission',
            'source_table' => $lead->getTable(),
            'source_record_id' => $lead->id,
            'page_key' => $lead->page_key,
            'source_url' => $lead->source_url,
            'metadata' => array_filter([
                'lead_type' => $lead->type,
                'lead_box_id' => $lead->lead_box_id,
                'lead_slot_key' => $lead->lead_slot_key,
                'acquisition_id' => $lead->acquisition_id,
                'service_id' => $lead->service_id,
                'acquisition_path_id' => $lead->acquisition_path_id,
                'acquisition_path_key' => $lead->acquisition_path_key,
                'source_page_key' => $lead->source_page_key,
                'source_slot_key' => $lead->source_slot_key,
                'source_popup_key' => $lead->source_popup_key,
                'lead_status' => $lead->lead_status,
            ], fn ($value) => $value !== null),
        ]);

        AcquisitionEvent::query()->create([
            'acquisition_contact_id' => $contact->id,
            'acquisition_company_id' => $contact->acquisition_company_id,
            'acquisition_person_id' => $contact->acquisition_person_id,
            'event_type' => 'lead_submission',
            'channel' => 'web',
            'actor_type' => 'system',
            'related_table' => $lead->getTable(),
            'related_id' => $lead->id,
            'summary' => 'Lead submission received.',
            'details' => array_filter([
                'lead_type' => $lead->type,
                'page_key' => $lead->page_key,
                'lead_box_id' => $lead->lead_box_id,
                'lead_slot_key' => $lead->lead_slot_key,
                'acquisition_id' => $lead->acquisition_id,
                'service_id' => $lead->service_id,
                'acquisition_path_id' => $lead->acquisition_path_id,
                'acquisition_path_key' => $lead->acquisition_path_key,
                'source_page_key' => $lead->source_page_key,
                'source_slot_key' => $lead->source_slot_key,
                'source_popup_key' => $lead->source_popup_key,
                'entry_url' => $lead->entry_url,
                'lead_status' => $lead->lead_status,
                'source_url' => $lead->source_url,
            ], fn ($value) => $value !== null),
            'occurred_at' => $lead->created_at ?? now(),
        ]);

        $this->logger->info('leads', 'leads', 'lead_acquisition_contact_attached', [
            'entity' => $lead,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'outcome' => 'attached',
            'context' => $this->leadLogContext($lead, [
                'acquisition_contact_id' => $contact->id,
            ]),
        ]);
    }

    private function resolveAssignmentForRequest(Request $request): ?LeadAssignment
    {
        $leadSlotKey = trim((string) $request->input('lead_slot_key', ''));

        if ($leadSlotKey === '') {
            return null;
        }

        $slot = LeadSlot::query()
            ->with('assignment')
            ->where('key', $leadSlotKey)
            ->first();

        return $slot?->assignment;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function leadLogContext(Lead $lead, array $context = []): array
    {
        return array_filter([
            'lead_type' => $lead->type,
            'page_key' => $lead->page_key,
            'lead_box_id' => $lead->lead_box_id,
            'lead_slot_key' => $lead->lead_slot_key,
            'acquisition_id' => $lead->acquisition_id,
            'service_id' => $lead->service_id,
            'acquisition_path_id' => $lead->acquisition_path_id,
            'acquisition_path_key' => $lead->acquisition_path_key,
            'source_popup_key' => $lead->source_popup_key,
            'acquisition_contact_id' => $lead->acquisition_contact_id,
            ...$context,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
