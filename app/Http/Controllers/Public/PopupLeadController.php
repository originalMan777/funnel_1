<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcquisitionEvent;
use App\Models\AcquisitionSource;
use App\Models\Popup;
use App\Models\PopupLead;
use App\Services\Acquisition\AcquisitionContactResolver;
use App\Services\Analytics\AnalyticsObservationService;
use App\Services\Communications\CommunicationService;
use App\Services\Leads\LeadAcquisitionResolver;
use App\Services\Logging\StructuredEventLogger;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PopupLeadController extends Controller
{
    public function __construct(
        private readonly AcquisitionContactResolver $acquisitionContactResolver,
        private readonly LeadAcquisitionResolver $leadAcquisitionResolver,
        private readonly AnalyticsObservationService $analyticsObservationService,
        private readonly SecurityAuditLogger $securityAuditLogger,
        private readonly CommunicationService $communicationService,
        private readonly StructuredEventLogger $logger,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $baseValidated = $request->validate([
            'popup_id' => ['required', 'integer', Rule::exists('popups', 'id')],
            'page_key' => ['nullable', 'string', 'max:100'],
            'source_url' => ['nullable', 'string', 'max:2048'],
            'website' => ['nullable', 'string', 'max:1'],
            'acquisition_path_key' => ['nullable', 'string', 'max:255'],
            'acquisition_slug' => ['nullable', 'string', 'max:255'],
            'service_slug' => ['nullable', 'string', 'max:255'],
            'source_popup_key' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($baseValidated['website'])) {
            $this->logger->warning('leads', 'leads', 'popup_submission_rejected', [
                'request' => $request,
                'entity_type' => 'popup_submission',
                'outcome' => 'rejected',
                'reason' => 'honeypot_triggered',
                'context' => [
                    'popup_id' => (int) $baseValidated['popup_id'],
                    'page_key' => $baseValidated['page_key'] ?? null,
                ],
            ]);

            throw ValidationException::withMessages([
                'popup' => 'Invalid submission.',
            ]);
        }

        $popup = Popup::query()
            ->whereKey($baseValidated['popup_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $requestedPage = $baseValidated['page_key'] ?? null;

        if (! $this->passesAudienceRule($popup, (bool) $request->user())) {
            $this->logger->warning('leads', 'leads', 'popup_submission_rejected', [
                'request' => $request,
                'entity_type' => 'popup',
                'entity_id' => $popup->id,
                'outcome' => 'rejected',
                'reason' => 'audience_not_allowed',
                'context' => [
                    'popup_slug' => $popup->slug,
                    'page_key' => $requestedPage,
                ],
            ]);

            throw ValidationException::withMessages([
                'popup' => 'This popup is not available.',
            ]);
        }

        if (! empty($popup->target_pages)) {
            $allowedPages = collect($popup->target_pages)->map(fn ($p) => (string) $p);

            if ($requestedPage === null || ! $allowedPages->contains($requestedPage)) {
                $this->logger->warning('leads', 'leads', 'popup_submission_rejected', [
                    'request' => $request,
                    'entity_type' => 'popup',
                    'entity_id' => $popup->id,
                    'outcome' => 'rejected',
                    'reason' => 'page_not_allowed',
                    'context' => [
                        'popup_slug' => $popup->slug,
                        'page_key' => $requestedPage,
                    ],
                ]);

                throw ValidationException::withMessages([
                    'popup' => 'This popup is not valid for this page.',
                ]);
            }
        }

        if ($popup->suppression_scope === 'all_lead_popups') {
            if ($request->cookie('nojo_lead_captured') === '1') {
                $this->logger->info('leads', 'leads', 'popup_submission_rejected', [
                    'request' => $request,
                    'entity_type' => 'popup',
                    'entity_id' => $popup->id,
                    'outcome' => 'rejected',
                    'reason' => 'suppressed_existing_lead_capture',
                    'context' => [
                        'popup_slug' => $popup->slug,
                        'page_key' => $requestedPage,
                    ],
                ]);

                throw ValidationException::withMessages([
                    'popup' => 'You have already submitted your information.',
                ]);
            }
        }

        $popupCookie = 'nojo_popup_submitted_'.Str::slug((string) $popup->slug, '_');

        if ($request->cookie($popupCookie) === '1') {
            $this->logger->info('leads', 'leads', 'popup_submission_rejected', [
                'request' => $request,
                'entity_type' => 'popup',
                'entity_id' => $popup->id,
                'outcome' => 'rejected',
                'reason' => 'popup_cookie_replay',
                'context' => [
                    'popup_slug' => $popup->slug,
                    'page_key' => $requestedPage,
                ],
            ]);

            throw ValidationException::withMessages([
                'popup' => 'You have already submitted this popup.',
            ]);
        }

        $recentSubmission = PopupLead::query()
            ->where('ip_address', $request->ip())
            ->where('popup_id', $popup->id)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($recentSubmission) {
            $this->logger->warning('leads', 'leads', 'popup_submission_throttled', [
                'request' => $request,
                'entity_type' => 'popup',
                'entity_id' => $popup->id,
                'outcome' => 'throttled',
                'reason' => 'recent_ip_submission',
                'context' => [
                    'popup_slug' => $popup->slug,
                    'page_key' => $requestedPage,
                ],
            ]);

            throw ValidationException::withMessages([
                'popup' => 'Too many submissions. Please wait a moment.',
            ]);
        }

        $fields = collect($popup->form_fields ?? [])->values();

        if ($fields->isEmpty()) {
            $fields = collect(['email']);
        }

        $payload = $request->validate([
            'name' => $fields->contains('name')
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'email' => $fields->contains('email')
                ? ['required', 'email:rfc', 'max:255']
                : ['nullable', 'email:rfc', 'max:255'],
            'phone' => $fields->contains('phone')
                ? ['required', 'string', 'max:50']
                : ['nullable', 'string', 'max:50'],
            'message' => $fields->contains('message')
                ? ['required', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'],
        ]);

        $payload = [
            'name' => isset($payload['name']) ? Str::squish(strip_tags((string) $payload['name'])) : null,
            'email' => isset($payload['email']) ? trim(Str::lower((string) $payload['email'])) : null,
            'phone' => isset($payload['phone']) ? trim(strip_tags((string) $payload['phone'])) : null,
            'message' => isset($payload['message']) ? trim((string) $payload['message']) : null,
        ];

        if (! empty($payload['email'])) {
            $recentEmailSubmission = PopupLead::query()
                ->where('popup_id', $popup->id)
                ->where('email', $payload['email'])
                ->where('created_at', '>=', now()->subMinutes(10))
                ->exists();

            if ($recentEmailSubmission) {
                $this->logger->warning('leads', 'leads', 'popup_submission_throttled', [
                    'request' => $request,
                    'entity_type' => 'popup',
                    'entity_id' => $popup->id,
                    'outcome' => 'throttled',
                    'reason' => 'recent_email_submission',
                    'context' => [
                        'popup_slug' => $popup->slug,
                        'page_key' => $requestedPage,
                    ],
                ]);

                throw ValidationException::withMessages([
                    'popup' => 'Too many submissions. Please wait a moment.',
                ]);
            }
        }

        $derivedSourceUrl = $this->deriveSafeSourceUrl($request);

        $acquisitionContext = $this->leadAcquisitionResolver->resolveForPopupRequest(
            request: $request,
            popupSlug: $popup->slug,
            popupLeadType: $popup->lead_type,
        );

        $this->logger->info('leads', 'leads', 'popup_submission_accepted', [
            'request' => $request,
            'entity_type' => 'popup',
            'entity_id' => $popup->id,
            'outcome' => 'accepted',
            'context' => [
                'popup_slug' => $popup->slug,
                'page_key' => $requestedPage,
                'lead_type' => $popup->lead_type,
                'acquisition_id' => $acquisitionContext['acquisitionId'],
                'service_id' => $acquisitionContext['serviceId'],
                'acquisition_path_id' => $acquisitionContext['acquisitionPathId'],
                'acquisition_path_key' => $acquisitionContext['acquisitionPathKey'],
                'source_popup_key' => $acquisitionContext['sourcePopupKey'],
            ],
        ]);

        $lead = DB::transaction(function () use ($acquisitionContext, $derivedSourceUrl, $payload, $popup, $request, $requestedPage): PopupLead {
            $lead = PopupLead::create([
                'popup_id' => $popup->id,
                'page_key' => $requestedPage,
                'source_url' => $derivedSourceUrl,
                'lead_type' => $popup->lead_type,
                'name' => $payload['name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'message' => $payload['message'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'popup_slug' => $popup->slug,
                    'popup_type' => $popup->type,
                    'popup_role' => $popup->role,
                    'trigger_type' => $popup->trigger_type,
                    'target_pages' => $popup->target_pages ?? [],
                    'post_submit_action' => $popup->post_submit_action,
                    'acquisition_context' => array_filter([
                        'acquisition_id' => $acquisitionContext['acquisitionId'],
                        'service_id' => $acquisitionContext['serviceId'],
                        'acquisition_path_id' => $acquisitionContext['acquisitionPathId'],
                        'acquisition_path_key' => $acquisitionContext['acquisitionPathKey'],
                        'source_page_key' => $acquisitionContext['sourcePageKey'],
                        'source_popup_key' => $acquisitionContext['sourcePopupKey'],
                    ], fn ($value) => $value !== null),
                ],
            ]);

            $this->attachPopupLeadToAcquisition($lead);

            return $lead;
        });

        $this->logger->info('leads', 'leads', 'popup_lead_persisted', [
            'request' => $request,
            'entity' => $lead,
            'entity_type' => 'popup_lead',
            'entity_id' => $lead->id,
            'outcome' => 'created',
            'context' => $this->popupLeadLogContext($lead),
        ]);

        $this->analyticsObservationService->recordPopupSubmission($request, $lead, $popup);

        $this->communicationService->recordAndQueue(
            eventKey: 'popup.submitted',
            subject: $lead,
            acquisitionContactId: $lead->acquisition_contact_id,
            payload: [
                'popup_lead_id' => $lead->id,
                'popup_id' => $lead->popup_id,
                'popup_slug' => $popup->slug,
                'page_key' => $lead->page_key,
                'email' => $lead->email,
            ],
        );

        $cookieMinutes = 60 * 24 * 365;
        $secureCookie = (bool) (config('session.secure') ?? $request->isSecure());

        Cookie::queue(Cookie::make(
            $popupCookie,
            '1',
            $cookieMinutes,
            '/',
            null,
            $secureCookie,
            true,
            false,
            'lax'
        ));

        if ($popup->suppression_scope === 'all_lead_popups') {
            Cookie::queue(Cookie::make(
                'nojo_lead_captured',
                '1',
                $cookieMinutes,
                '/',
                null,
                $secureCookie,
                true,
                false,
                'lax'
            ));
        }

        $this->securityAuditLogger->log(
            event: 'popup_lead_created',
            request: $request,
            userId: null,
            entityType: 'popup_lead',
            entityId: (int) $lead->id,
            context: [
                'popup_id' => $popup->id,
                'popup_slug' => $popup->slug,
                'page_key' => $requestedPage,
            ]
        );

        return back()->with(
            'popupLeadSuccess',
            $popup->success_message ?: 'Thanks. We received your information.'
        );
    }

    private function deriveSafeSourceUrl(Request $request): ?string
    {
        $referer = trim((string) $request->headers->get('referer', ''));

        if ($referer === '') {
            return null;
        }

        if (! filter_var($referer, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $referer;
    }

    private function passesAudienceRule(Popup $popup, bool $isAuthenticated): bool
    {
        return match ($popup->audience) {
            'authenticated' => $isAuthenticated,
            'guests' => ! $isAuthenticated,
            default => true,
        };
    }

    private function attachPopupLeadToAcquisition(PopupLead $popupLead): void
    {
        $contact = $this->acquisitionContactResolver->resolveFromPopupLead($popupLead);

        $popupLead->forceFill([
            'acquisition_contact_id' => $contact->id,
        ])->save();

        AcquisitionSource::query()->create([
            'acquisition_contact_id' => $contact->id,
            'source_type' => 'popup_submission',
            'source_table' => $popupLead->getTable(),
            'source_record_id' => $popupLead->id,
            'page_key' => $popupLead->page_key,
            'source_url' => $popupLead->source_url,
            'metadata' => array_filter([
                'popup_id' => $popupLead->popup_id,
                'lead_type' => $popupLead->lead_type,
                'popup_slug' => $popupLead->metadata['popup_slug'] ?? null,
                'acquisition_id' => $popupLead->metadata['acquisition_context']['acquisition_id'] ?? null,
                'service_id' => $popupLead->metadata['acquisition_context']['service_id'] ?? null,
                'acquisition_path_id' => $popupLead->metadata['acquisition_context']['acquisition_path_id'] ?? null,
                'acquisition_path_key' => $popupLead->metadata['acquisition_context']['acquisition_path_key'] ?? null,
                'source_popup_key' => $popupLead->metadata['acquisition_context']['source_popup_key'] ?? null,
            ], fn ($value) => $value !== null),
        ]);

        AcquisitionEvent::query()->create([
            'acquisition_contact_id' => $contact->id,
            'acquisition_company_id' => $contact->acquisition_company_id,
            'acquisition_person_id' => $contact->acquisition_person_id,
            'event_type' => 'popup_submission',
            'channel' => 'web',
            'actor_type' => 'system',
            'related_table' => $popupLead->getTable(),
            'related_id' => $popupLead->id,
            'summary' => 'Popup lead submission received.',
            'details' => array_filter([
                'popup_id' => $popupLead->popup_id,
                'page_key' => $popupLead->page_key,
                'lead_type' => $popupLead->lead_type,
                'source_url' => $popupLead->source_url,
                'popup_slug' => $popupLead->metadata['popup_slug'] ?? null,
                'acquisition_id' => $popupLead->metadata['acquisition_context']['acquisition_id'] ?? null,
                'service_id' => $popupLead->metadata['acquisition_context']['service_id'] ?? null,
                'acquisition_path_id' => $popupLead->metadata['acquisition_context']['acquisition_path_id'] ?? null,
                'acquisition_path_key' => $popupLead->metadata['acquisition_context']['acquisition_path_key'] ?? null,
                'source_popup_key' => $popupLead->metadata['acquisition_context']['source_popup_key'] ?? null,
            ], fn ($value) => $value !== null),
            'occurred_at' => $popupLead->created_at ?? now(),
        ]);

        $this->logger->info('leads', 'leads', 'popup_lead_acquisition_contact_attached', [
            'entity' => $popupLead,
            'entity_type' => 'popup_lead',
            'entity_id' => $popupLead->id,
            'outcome' => 'attached',
            'context' => $this->popupLeadLogContext($popupLead, [
                'acquisition_contact_id' => $contact->id,
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function popupLeadLogContext(PopupLead $popupLead, array $context = []): array
    {
        return array_filter([
            'popup_id' => $popupLead->popup_id,
            'page_key' => $popupLead->page_key,
            'lead_type' => $popupLead->lead_type,
            'source_url_present' => filled($popupLead->source_url),
            'acquisition_contact_id' => $popupLead->acquisition_contact_id,
            'popup_slug' => $popupLead->metadata['popup_slug'] ?? null,
            'acquisition_id' => $popupLead->metadata['acquisition_context']['acquisition_id'] ?? null,
            'service_id' => $popupLead->metadata['acquisition_context']['service_id'] ?? null,
            'acquisition_path_id' => $popupLead->metadata['acquisition_context']['acquisition_path_id'] ?? null,
            'acquisition_path_key' => $popupLead->metadata['acquisition_context']['acquisition_path_key'] ?? null,
            'source_popup_key' => $popupLead->metadata['acquisition_context']['source_popup_key'] ?? null,
            ...$context,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
