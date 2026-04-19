<?php

namespace App\Services\Communications;

use App\Jobs\ProcessCommunicationEventJob;
use App\Models\AcquisitionContact;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\Lead;
use App\Models\MarketingContactSync;
use App\Models\PopupLead;
use App\Services\Campaigns\CampaignEnrollmentService;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Communications\DTOs\MarketingAction;
use App\Services\Communications\DTOs\MarketingActionResult;
use App\Services\Communications\DTOs\MarketingContact;
use App\Services\Communications\DTOs\TransactionalEmail;
use App\Services\Logging\StructuredEventLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CommunicationService
{
    public function __construct(
        private readonly TransactionalEmailProvider $transactionalEmailProvider,
        private readonly MarketingProvider $marketingProvider,
        private readonly CommunicationRuntimeConfig $runtimeConfig,
        private readonly CommunicationTemplateRuntimeResolver $templateRuntimeResolver,
        private readonly CampaignEnrollmentService $campaignEnrollmentService,
        private readonly StructuredEventLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordAndQueue(
        string $eventKey,
        Model $subject,
        ?int $acquisitionContactId,
        array $payload = [],
    ): ?CommunicationEvent {
        try {
            $event = CommunicationEvent::query()->create([
                'event_key' => $eventKey,
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'acquisition_contact_id' => $acquisitionContactId,
                'status' => CommunicationEvent::STATUS_PENDING,
                'payload' => $payload,
            ]);

            $this->logger->info('communications', 'communications', 'communication_event_recorded', [
                'entity' => $event,
                'entity_type' => 'communication_event',
                'entity_id' => $event->id,
                'outcome' => 'recorded',
                'context' => $this->eventLogContext($event, [
                    'subject_type' => class_basename($subject),
                    'subject_id' => $subject->getKey(),
                ]),
            ]);

            $this->enrollCampaignsForRecordedEvent($eventKey, $subject, $acquisitionContactId);
            $this->queueProcessing($event);

            return $event;
        } catch (Throwable $exception) {
            $this->logger->error('communications', 'communications', 'communication_event_record_failed', [
                'entity_type' => 'communication_subject',
                'entity_id' => $subject->getKey(),
                'outcome' => 'failed',
                'reason' => 'record_and_queue_failed',
                'context' => [
                    'event_key' => $eventKey,
                    'subject_type' => class_basename($subject),
                    'subject_id' => $subject->getKey(),
                    'lead_id' => $subject instanceof Lead ? $subject->getKey() : null,
                    'popup_lead_id' => $subject instanceof PopupLead ? $subject->getKey() : null,
                    'acquisition_contact_id' => $acquisitionContactId,
                    'exception_class' => $exception::class,
                    'exception_message' => $this->logger->safeExceptionMessage($exception),
                ],
            ]);
            report($exception);

            return null;
        }
    }

    /**
     * Backward-compatible alias for Phase 1 callers.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordAndDispatch(
        string $eventKey,
        Model $subject,
        ?int $acquisitionContactId,
        array $payload = [],
    ): ?CommunicationEvent {
        return $this->recordAndQueue($eventKey, $subject, $acquisitionContactId, $payload);
    }

    private function enrollCampaignsForRecordedEvent(
        string $eventKey,
        Model $subject,
        ?int $acquisitionContactId,
    ): void {
        if (! in_array($eventKey, [
            'lead.created',
            'lead.consultation_requested',
            'contact.requested',
            'popup.submitted',
        ], true)) {
            return;
        }

        $context = [
            'acquisition_contact_id' => $acquisitionContactId,
        ];

        if ($subject instanceof Lead) {
            $context['lead_id'] = $subject->getKey();
        } elseif ($subject instanceof PopupLead) {
            $context['popup_lead_id'] = $subject->getKey();
        } else {
            return;
        }

        try {
            $this->campaignEnrollmentService->enrollForTrigger($eventKey, $context);
        } catch (Throwable $exception) {
            $this->logger->error('communications', 'communications', 'communication_event_campaign_enrollment_failed', [
                'entity_type' => 'communication_subject',
                'entity_id' => $subject->getKey(),
                'outcome' => 'failed',
                'reason' => 'campaign_enrollment_failed',
                'context' => [
                    'event_key' => $eventKey,
                    'subject_type' => class_basename($subject),
                    'acquisition_contact_id' => $acquisitionContactId,
                    'exception_class' => $exception::class,
                    'exception_message' => $this->logger->safeExceptionMessage($exception),
                ],
            ]);
            report($exception);
        }
    }

    public function queueProcessing(CommunicationEvent $event): void
    {
        ProcessCommunicationEventJob::dispatch($event->id)
            ->afterCommit();

        $this->logger->info('communications', 'communications', 'communication_event_queued', [
            'entity' => $event,
            'entity_type' => 'communication_event',
            'entity_id' => $event->id,
            'outcome' => 'queued',
            'context' => $this->eventLogContext($event, [
                'queue_connection' => (string) config('communications.queue_connection', 'database'),
                'queue' => (string) config('communications.queue', 'communications'),
            ]),
        ]);
    }

    public function requeueEvent(CommunicationEvent $event): void
    {
        $event->refresh();

        $event->forceFill([
            'status' => CommunicationEvent::STATUS_PENDING,
            'processed_at' => null,
        ])->save();

        $this->logger->info('communications', 'communications', 'communication_event_requeued', [
            'entity' => $event,
            'entity_type' => 'communication_event',
            'entity_id' => $event->id,
            'outcome' => 'requeued',
            'context' => $this->eventLogContext($event),
        ]);

        $this->queueProcessing($event);
    }

    public function processEvent(int $eventId, ?string $jobId = null): bool
    {
        $event = $this->reserveEventForProcessing($eventId, $jobId);

        if ($event === null) {
            return true;
        }

        $event->loadMissing('subject');

        if ($event->subject === null) {
            $this->finalizeEvent($event, CommunicationEvent::STATUS_FAILED);

            $this->logger->warning('communications', 'communications', 'communication_event_subject_missing', [
                'entity' => $event,
                'entity_type' => 'communication_event',
                'entity_id' => $event->id,
                'job_id' => $jobId,
                'outcome' => 'failed',
                'reason' => 'subject_missing',
                'context' => $this->eventLogContext($event),
            ]);

            return false;
        }

        $event->loadMissing('acquisitionContact');
        $emails = $this->emailsFor($event);
        $marketingActions = $this->marketingActionsFor($event);

        if ($emails === [] && $marketingActions === []) {
            $this->finalizeEvent($event, CommunicationEvent::STATUS_SKIPPED);

            $this->logger->info('communications', 'communications', 'communication_event_no_actions_resolved', [
                'entity' => $event,
                'entity_type' => 'communication_event',
                'entity_id' => $event->id,
                'job_id' => $jobId,
                'outcome' => 'skipped',
                'reason' => 'no_actions_resolved',
                'context' => $this->eventLogContext($event, [
                    'email_actions' => 0,
                    'marketing_actions' => 0,
                ]),
            ]);

            return true;
        }

        $successfulDeliveries = 0;
        $hadFailures = false;
        $totalActions = count($emails) + count($marketingActions);
        $emailActionCount = count($emails);
        $marketingActionCount = count($marketingActions);
        $emailFailures = 0;
        $marketingFailures = 0;
        $dedupeSkips = 0;
        $emailDedupeSkips = 0;
        $marketingDedupeSkips = 0;
        $templateBoundEmailActions = 0;

        foreach ($emails as $email) {
            if ($this->hasSuccessfulDelivery($event, $email->actionKey, $email->toEmail)) {
                $successfulDeliveries++;
                $dedupeSkips++;
                $emailDedupeSkips++;

                continue;
            }

            $email = $this->applyTemplateBinding($event, $email);
            $templateBoundEmailActions += $email->communicationTemplateId !== null ? 1 : 0;
            $result = $this->transactionalEmailProvider->send($email);

            CommunicationDelivery::query()->create([
                'communication_event_id' => $event->id,
                'action_key' => $email->actionKey,
                'channel' => 'email',
                'provider' => $result->provider,
                'recipient_email' => $email->toEmail,
                'recipient_name' => $email->toName,
                'subject' => $email->subject,
                'status' => $result->successful
                    ? CommunicationDelivery::STATUS_SENT
                    : CommunicationDelivery::STATUS_FAILED,
                'provider_message_id' => $result->providerMessageId,
                'error_message' => $result->errorMessage,
                'payload' => $email->payload,
                'sent_at' => $result->successful ? now() : null,
                'communication_template_id' => $email->communicationTemplateId,
                'communication_template_version_id' => $email->communicationTemplateVersionId,
            ]);

            if ($result->successful) {
                $successfulDeliveries++;
            } else {
                $hadFailures = true;
                $emailFailures++;
            }
        }

        $emailProviders = CommunicationDelivery::query()
            ->where('communication_event_id', $event->id)
            ->where('channel', 'email')
            ->pluck('provider')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->logger->{$emailFailures > 0 ? 'warning' : 'info'}(
            'communications',
            'communications',
            'communication_email_delivery_summary',
            [
                'entity' => $event,
                'entity_type' => 'communication_event',
                'entity_id' => $event->id,
                'job_id' => $jobId,
                'outcome' => $emailFailures > 0 ? 'partial_failure' : 'processed',
                'reason' => $emailFailures > 0 ? 'delivery_failures' : null,
                'context' => $this->eventLogContext($event, [
                    'channel' => 'email',
                    'action_keys' => collect($emails)->map(fn (TransactionalEmail $email) => $email->actionKey)->values()->all(),
                    'providers' => $emailProviders,
                    'email_actions' => $emailActionCount,
                    'successful_deliveries' => max(0, $emailActionCount - $emailFailures),
                    'email_failures' => $emailFailures,
                    'dedupe_skips' => $emailDedupeSkips,
                    'template_ids' => collect($emails)->map(fn (TransactionalEmail $email) => $email->communicationTemplateId)->filter()->unique()->values()->all(),
                    'template_version_ids' => collect($emails)->map(fn (TransactionalEmail $email) => $email->communicationTemplateVersionId)->filter()->unique()->values()->all(),
                    'masked_recipient_emails' => collect($emails)->map(fn (TransactionalEmail $email) => $this->maskEmail($email->toEmail))->unique()->values()->all(),
                ]),
            ],
        );

        foreach ($marketingActions as $action) {
            if ($this->hasSuccessfulDelivery($event, $action->actionKey, $action->contact->email)) {
                $successfulDeliveries++;
                $dedupeSkips++;
                $marketingDedupeSkips++;

                continue;
            }

            $result = $this->sendMarketingAction($action);

            CommunicationDelivery::query()->create([
                'communication_event_id' => $event->id,
                'action_key' => $action->actionKey,
                'channel' => 'marketing',
                'provider' => $result->provider,
                'recipient_email' => $action->contact->email,
                'recipient_name' => $action->contact->name,
                'subject' => null,
                'status' => $result->successful
                    ? CommunicationDelivery::STATUS_SENT
                    : CommunicationDelivery::STATUS_FAILED,
                'provider_message_id' => $result->externalContactId,
                'error_message' => $result->errorMessage,
                'payload' => $action->payload,
                'sent_at' => $result->successful ? now() : null,
            ]);

            $this->recordMarketingSyncState($action, $result);

            if ($result->successful) {
                $successfulDeliveries++;
            } else {
                $hadFailures = true;
                $marketingFailures++;
            }
        }

        $this->logger->{$marketingFailures > 0 ? 'warning' : 'info'}(
            'communications',
            'communications',
            'communication_marketing_action_summary',
            [
                'entity' => $event,
                'entity_type' => 'communication_event',
                'entity_id' => $event->id,
                'job_id' => $jobId,
                'outcome' => $marketingFailures > 0 ? 'partial_failure' : 'processed',
                'reason' => $marketingFailures > 0 ? 'delivery_failures' : null,
                'context' => $this->eventLogContext($event, [
                    'channel' => 'marketing',
                    'action_keys' => collect($marketingActions)->map(fn (MarketingAction $action) => $action->actionKey)->values()->all(),
                    'providers' => collect($marketingActions)->map(fn (MarketingAction $action) => $this->runtimeConfig->marketingProvider())->filter()->unique()->values()->all(),
                    'marketing_actions' => $marketingActionCount,
                    'successful_deliveries' => max(0, $marketingActionCount - $marketingFailures),
                    'marketing_failures' => $marketingFailures,
                    'dedupe_skips' => $marketingDedupeSkips,
                    'masked_recipient_emails' => collect($marketingActions)->map(fn (MarketingAction $action) => $this->maskEmail($action->contact->email))->unique()->values()->all(),
                ]),
            ],
        );

        $status = $successfulDeliveries === $totalActions
            ? CommunicationEvent::STATUS_PROCESSED
            : ($successfulDeliveries > 0
                ? CommunicationEvent::STATUS_PARTIAL_FAILURE
                : CommunicationEvent::STATUS_FAILED);

        $this->finalizeEvent($event, $status);

        $level = $hadFailures ? 'warning' : 'info';

        $this->logger->{$level}('communications', 'communications', 'communication_event_processed', [
            'entity' => $event,
            'entity_type' => 'communication_event',
            'entity_id' => $event->id,
            'job_id' => $jobId,
            'outcome' => $status,
            'reason' => $hadFailures ? 'delivery_failures' : null,
            'context' => $this->eventLogContext($event, [
                'total_actions' => $totalActions,
                'email_actions' => $emailActionCount,
                'marketing_actions' => $marketingActionCount,
                'successful_deliveries' => $successfulDeliveries,
                'email_failures' => $emailFailures,
                'marketing_failures' => $marketingFailures,
                'dedupe_skips' => $dedupeSkips,
                'template_bound_email_actions' => $templateBoundEmailActions,
                'final_status' => $status,
                'channel' => $emailActionCount > 0 && $marketingActionCount > 0
                    ? 'multi'
                    : ($emailActionCount > 0 ? 'email' : 'marketing'),
            ]),
        ]);

        return ! $hadFailures;
    }

    public function markProcessingEventAsFailed(int $eventId, ?string $jobId = null, ?Throwable $exception = null): void
    {
        $event = CommunicationEvent::query()->find($eventId);

        if (! $event) {
            return;
        }

        if ($event->status !== CommunicationEvent::STATUS_PROCESSING) {
            return;
        }

        $this->finalizeEvent($event, CommunicationEvent::STATUS_FAILED);

        $this->logger->error('communications', 'communications', 'communication_event_processing_failed', [
            'entity' => $event,
            'entity_type' => 'communication_event',
            'entity_id' => $event->id,
            'job_id' => $jobId,
            'outcome' => 'failed',
            'reason' => 'job_failed',
            'context' => $this->eventLogContext($event, [
                'exception_class' => $exception ? $exception::class : null,
                'exception_message' => $exception ? $this->logger->safeExceptionMessage($exception) : null,
            ]),
        ]);
    }

    /**
     * @return array<int, TransactionalEmail>
     */
    private function emailsFor(CommunicationEvent $event): array
    {
        $subject = $event->subject;

        return match ($event->event_key) {
            'lead.consultation_requested' => $subject instanceof Lead ? $this->consultationEmails($subject) : [],
            'contact.requested' => $subject instanceof Lead ? $this->contactEmails($subject) : [],
            'lead.created' => $subject instanceof Lead ? $this->genericLeadEmails($subject) : [],
            'popup.submitted' => $subject instanceof PopupLead ? $this->popupEmails($subject) : [],
            default => [],
        };
    }

    /**
     * @return array<int, TransactionalEmail>
     */
    private function consultationEmails(Lead $lead): array
    {
        return array_values(array_filter([
            $this->userConfirmationEmail(
                actionKey: 'consultation.user_confirmation',
                recipientEmail: $lead->email,
                recipientName: $lead->first_name,
                subject: 'We received your consultation request',
                headline: 'Your consultation request is in.',
                lines: [
                    'Thanks for reaching out. We received your consultation request and will follow up soon.',
                    'Phone: '.(Arr::get($lead->payload, 'phone') ?: 'Not provided'),
                    'Details: '.(Arr::get($lead->payload, 'details') ?: 'None provided'),
                ],
                payload: [
                    'lead_id' => $lead->id,
                    'lead_type' => $lead->type,
                ],
            ),
            $this->adminNotificationEmail(
                actionKey: 'consultation.admin_notification',
                subject: 'New consultation request received',
                headline: 'A consultation request was submitted.',
                lines: [
                    'Name: '.$lead->first_name,
                    'Email: '.$lead->email,
                    'Phone: '.(Arr::get($lead->payload, 'phone') ?: 'Not provided'),
                    'Details: '.(Arr::get($lead->payload, 'details') ?: 'None provided'),
                ],
                payload: [
                    'lead_id' => $lead->id,
                    'lead_type' => $lead->type,
                ],
            ),
        ]));
    }

    /**
     * @return array<int, TransactionalEmail>
     */
    private function contactEmails(Lead $lead): array
    {
        return array_values(array_filter([
            $this->userConfirmationEmail(
                actionKey: 'contact.user_confirmation',
                recipientEmail: $lead->email,
                recipientName: $lead->first_name,
                subject: 'We received your message',
                headline: 'Your message is in.',
                lines: [
                    'Thanks for contacting us. We received your message and will get back to you soon.',
                    'Message: '.(Arr::get($lead->payload, 'message') ?: 'None provided'),
                ],
                payload: [
                    'lead_id' => $lead->id,
                    'lead_type' => $lead->type,
                ],
            ),
            $this->adminNotificationEmail(
                actionKey: 'contact.admin_notification',
                subject: 'New contact request received',
                headline: 'A contact request was submitted.',
                lines: [
                    'Name: '.$lead->first_name,
                    'Email: '.$lead->email,
                    'Message: '.(Arr::get($lead->payload, 'message') ?: 'None provided'),
                ],
                payload: [
                    'lead_id' => $lead->id,
                    'lead_type' => $lead->type,
                ],
            ),
        ]));
    }

    /**
     * @return array<int, TransactionalEmail>
     */
    private function genericLeadEmails(Lead $lead): array
    {
        return array_values(array_filter([
            $this->userConfirmationEmail(
                actionKey: 'lead.user_confirmation',
                recipientEmail: $lead->email,
                recipientName: $lead->first_name,
                subject: 'We received your request',
                headline: 'Your request is in.',
                lines: [
                    'Thanks for your interest. We received your request and will follow up soon.',
                    'Lead type: '.$lead->type,
                ],
                payload: [
                    'lead_id' => $lead->id,
                    'lead_type' => $lead->type,
                    'lead_box_id' => $lead->lead_box_id,
                ],
            ),
            $this->adminNotificationEmail(
                actionKey: 'lead.admin_notification',
                subject: 'New lead received',
                headline: 'A lead was submitted.',
                lines: [
                    'Name: '.$lead->first_name,
                    'Email: '.$lead->email,
                    'Lead type: '.$lead->type,
                    'Lead box ID: '.($lead->lead_box_id ?: 'None'),
                ],
                payload: [
                    'lead_id' => $lead->id,
                    'lead_type' => $lead->type,
                    'lead_box_id' => $lead->lead_box_id,
                ],
            ),
        ]));
    }

    /**
     * @return array<int, TransactionalEmail>
     */
    private function popupEmails(PopupLead $popupLead): array
    {
        return array_values(array_filter([
            $this->userConfirmationEmail(
                actionKey: 'popup.user_confirmation',
                recipientEmail: $popupLead->email,
                recipientName: $popupLead->name,
                subject: 'We received your information',
                headline: 'Your popup submission is in.',
                lines: [
                    'Thanks. We received your information and will follow up if needed.',
                    'Popup type: '.($popupLead->lead_type ?: 'general'),
                ],
                payload: [
                    'popup_lead_id' => $popupLead->id,
                    'popup_id' => $popupLead->popup_id,
                    'lead_type' => $popupLead->lead_type,
                ],
            ),
        ]));
    }

    /**
     * @return array<int, MarketingAction>
     */
    private function marketingActionsFor(CommunicationEvent $event): array
    {
        $subject = $event->subject;

        return match ($event->event_key) {
            'lead.consultation_requested' => $subject instanceof Lead ? $this->consultationMarketingActions($subject, $event->acquisitionContact) : [],
            'contact.requested' => $subject instanceof Lead ? $this->contactMarketingActions($subject, $event->acquisitionContact) : [],
            'lead.created' => $subject instanceof Lead ? $this->leadMarketingActions($subject, $event->acquisitionContact) : [],
            'popup.submitted' => $subject instanceof PopupLead ? $this->popupMarketingActions($subject, $event->acquisitionContact) : [],
            default => [],
        };
    }

    /**
     * @return array<int, MarketingAction>
     */
    private function consultationMarketingActions(Lead $lead, ?AcquisitionContact $contact): array
    {
        $marketingContact = $this->marketingContactFromLead($lead, $contact);

        if ($marketingContact === null) {
            return [];
        }

        return array_values(array_filter([
            $this->syncContactAction(
                actionKey: 'marketing.sync_contact',
                contact: $marketingContact,
                payload: ['lead_id' => $lead->id, 'event_key' => 'lead.consultation_requested'],
            ),
            $this->addToAudienceAction(
                actionKey: 'marketing.audience.consultation',
                contact: $marketingContact,
                audienceKey: 'audience.consultation',
                payload: ['lead_id' => $lead->id],
            ),
            $this->applyTagsAction(
                actionKey: 'marketing.tags.consultation',
                contact: $marketingContact,
                audienceKey: 'audience.consultation',
                tagKeys: $this->uniqueTagKeys(array_merge(
                    ['tag.consultation.requested'],
                    $this->leadContextTagKeys($lead),
                )),
                payload: ['lead_id' => $lead->id],
            ),
            $this->triggerAutomationAction(
                actionKey: 'marketing.trigger.consultation.requested',
                contact: $marketingContact,
                triggerKey: 'trigger.consultation.requested',
                payload: ['lead_id' => $lead->id],
            ),
        ]));
    }

    /**
     * @return array<int, MarketingAction>
     */
    private function contactMarketingActions(Lead $lead, ?AcquisitionContact $contact): array
    {
        $marketingContact = $this->marketingContactFromLead($lead, $contact);

        if ($marketingContact === null) {
            return [];
        }

        return array_values(array_filter([
            $this->syncContactAction(
                actionKey: 'marketing.sync_contact',
                contact: $marketingContact,
                payload: ['lead_id' => $lead->id, 'event_key' => 'contact.requested'],
            ),
            $this->applyTagsAction(
                actionKey: 'marketing.tags.contact',
                contact: $marketingContact,
                audienceKey: $this->runtimeConfig->defaultMarketingAudienceKey(),
                tagKeys: $this->uniqueTagKeys(array_merge(
                    ['tag.contact.requested'],
                    $this->leadContextTagKeys($lead),
                )),
                payload: ['lead_id' => $lead->id],
            ),
            $this->triggerAutomationAction(
                actionKey: 'marketing.trigger.contact.requested',
                contact: $marketingContact,
                triggerKey: 'trigger.contact.requested',
                payload: ['lead_id' => $lead->id],
            ),
        ]));
    }

    /**
     * @return array<int, MarketingAction>
     */
    private function leadMarketingActions(Lead $lead, ?AcquisitionContact $contact): array
    {
        $marketingContact = $this->marketingContactFromLead($lead, $contact);

        if ($marketingContact === null) {
            return [];
        }

        return array_values(array_filter([
            $this->syncContactAction(
                actionKey: 'marketing.sync_contact',
                contact: $marketingContact,
                payload: ['lead_id' => $lead->id, 'event_key' => 'lead.created'],
            ),
            $this->applyTagsAction(
                actionKey: 'marketing.tags.lead',
                contact: $marketingContact,
                audienceKey: $this->runtimeConfig->defaultMarketingAudienceKey(),
                tagKeys: $this->uniqueTagKeys(array_merge(
                    ['tag.lead.created'],
                    $this->leadContextTagKeys($lead),
                )),
                payload: ['lead_id' => $lead->id],
            ),
        ]));
    }

    /**
     * @return array<int, MarketingAction>
     */
    private function popupMarketingActions(PopupLead $popupLead, ?AcquisitionContact $contact): array
    {
        $marketingContact = $this->marketingContactFromPopupLead($popupLead, $contact);

        if ($marketingContact === null) {
            return [];
        }

        return array_values(array_filter([
            $this->syncContactAction(
                actionKey: 'marketing.sync_contact',
                contact: $marketingContact,
                payload: ['popup_lead_id' => $popupLead->id, 'event_key' => 'popup.submitted'],
            ),
            $this->applyTagsAction(
                actionKey: 'marketing.tags.popup',
                contact: $marketingContact,
                audienceKey: $this->runtimeConfig->defaultMarketingAudienceKey(),
                tagKeys: $this->uniqueTagKeys([
                    'tag.popup.submitted',
                    'tag.popup.lead_type.'.$popupLead->lead_type,
                    filled($popupLead->metadata['popup_slug'] ?? null)
                        ? 'tag.popup.slug.'.(string) $popupLead->metadata['popup_slug']
                        : null,
                ]),
                payload: ['popup_lead_id' => $popupLead->id],
            ),
            $this->triggerAutomationAction(
                actionKey: 'marketing.trigger.popup.submitted',
                contact: $marketingContact,
                triggerKey: 'trigger.popup.submitted',
                payload: ['popup_lead_id' => $popupLead->id],
            ),
        ]));
    }

    /**
     * @param  array<int, string>  $lines
     * @param  array<string, mixed>  $payload
     */
    private function userConfirmationEmail(
        string $actionKey,
        ?string $recipientEmail,
        ?string $recipientName,
        string $subject,
        string $headline,
        array $lines,
        array $payload,
    ): ?TransactionalEmail {
        if (blank($recipientEmail)) {
            return null;
        }

        return new TransactionalEmail(
            actionKey: $actionKey,
            toEmail: $recipientEmail,
            toName: $recipientName,
            subject: $subject,
            headline: $headline,
            lines: $lines,
            payload: $payload,
        );
    }

    /**
     * @param  array<int, string>  $lines
     * @param  array<string, mixed>  $payload
     */
    private function adminNotificationEmail(
        string $actionKey,
        string $subject,
        string $headline,
        array $lines,
        array $payload,
    ): ?TransactionalEmail {
        $recipientEmail = config('communications.admin_notification_email');
        $recipientEmail = $this->runtimeConfig->adminNotificationEmail();

        if (blank($recipientEmail)) {
            return null;
        }

        return new TransactionalEmail(
            actionKey: $actionKey,
            toEmail: $recipientEmail,
            toName: $this->runtimeConfig->adminNotificationName(),
            subject: $subject,
            headline: $headline,
            lines: $lines,
            payload: $payload,
        );
    }

    private function applyTemplateBinding(CommunicationEvent $event, TransactionalEmail $email): TransactionalEmail
    {
        $boundMessage = $this->templateRuntimeResolver->resolveForEvent($event, $email);

        if ($boundMessage === null) {
            return $email;
        }

        return new TransactionalEmail(
            actionKey: $email->actionKey,
            toEmail: $email->toEmail,
            toName: $email->toName,
            subject: $boundMessage->subject,
            headline: $boundMessage->headline ?? $email->headline,
            lines: $email->lines,
            payload: $email->payload,
            previewText: $boundMessage->previewText,
            htmlBody: $boundMessage->htmlBody,
            textBody: $boundMessage->textBody,
            communicationTemplateId: $boundMessage->templateId,
            communicationTemplateVersionId: $boundMessage->templateVersionId,
        );
    }

    private function sendMarketingAction(MarketingAction $action): MarketingActionResult
    {
        return match ($action->type) {
            MarketingAction::TYPE_SYNC_CONTACT => $this->marketingProvider->syncContact($action),
            MarketingAction::TYPE_ADD_TO_AUDIENCE => $this->marketingProvider->addToAudience($action),
            MarketingAction::TYPE_APPLY_TAGS => $this->marketingProvider->applyTags($action),
            MarketingAction::TYPE_TRIGGER_AUTOMATION => $this->marketingProvider->triggerAutomation($action),
            default => MarketingActionResult::failure('unknown', "Unknown marketing action type [{$action->type}]."),
        };
    }

    private function reserveEventForProcessing(int $eventId, ?string $jobId = null): ?CommunicationEvent
    {
        $staleProcessingCutoff = now()->subSeconds(
            (int) config('communications.processing_timeout_seconds', 300),
        );

        return DB::transaction(function () use ($eventId, $jobId, $staleProcessingCutoff): ?CommunicationEvent {
            $event = CommunicationEvent::query()
                ->lockForUpdate()
                ->find($eventId);

            if (! $event) {
                $this->logger->warning('communications', 'communications', 'communication_event_reserve_skipped', [
                    'entity_type' => 'communication_event',
                    'entity_id' => $eventId,
                    'job_id' => $jobId,
                    'outcome' => 'skipped',
                    'reason' => 'event_missing',
                ]);

                return null;
            }

            if (in_array($event->status, [
                CommunicationEvent::STATUS_PROCESSED,
                CommunicationEvent::STATUS_SKIPPED,
            ], true)) {
                $this->logger->info('communications', 'communications', 'communication_event_reserve_skipped', [
                    'entity' => $event,
                    'entity_type' => 'communication_event',
                    'entity_id' => $event->id,
                    'job_id' => $jobId,
                    'outcome' => 'skipped',
                    'reason' => 'already_finalized',
                    'context' => $this->eventLogContext($event),
                ]);

                return null;
            }

            if (
                $event->status === CommunicationEvent::STATUS_PROCESSING
                && $event->updated_at !== null
                && $event->updated_at->gt($staleProcessingCutoff)
            ) {
                $this->logger->info('communications', 'communications', 'communication_event_reserve_skipped', [
                    'entity' => $event,
                    'entity_type' => 'communication_event',
                    'entity_id' => $event->id,
                    'job_id' => $jobId,
                    'outcome' => 'skipped',
                    'reason' => 'already_processing',
                    'context' => $this->eventLogContext($event),
                ]);

                return null;
            }

            $event->forceFill([
                'status' => CommunicationEvent::STATUS_PROCESSING,
                'processed_at' => null,
            ])->save();

            $this->logger->info('communications', 'communications', 'communication_event_reserved', [
                'entity' => $event,
                'entity_type' => 'communication_event',
                'entity_id' => $event->id,
                'job_id' => $jobId,
                'outcome' => 'processing',
                'context' => $this->eventLogContext($event),
            ]);

            return $event;
        });
    }

    private function finalizeEvent(CommunicationEvent $event, string $status): void
    {
        $event->forceFill([
            'status' => $status,
            'processed_at' => now(),
        ])->save();
    }

    private function hasSuccessfulDelivery(CommunicationEvent $event, string $actionKey, string $recipientEmail): bool
    {
        return CommunicationDelivery::query()
            ->where('communication_event_id', $event->id)
            ->where('action_key', $actionKey)
            ->where('recipient_email', $recipientEmail)
            ->where('status', CommunicationDelivery::STATUS_SENT)
            ->exists();
    }

    private function marketingContactFromLead(Lead $lead, ?AcquisitionContact $contact): ?MarketingContact
    {
        if (blank($lead->email)) {
            return null;
        }

        $lead->loadMissing(['acquisition', 'service', 'acquisitionPath']);

        return new MarketingContact(
            acquisitionContactId: $contact?->id,
            email: $lead->email,
            name: $lead->first_name,
            phone: Arr::get($lead->payload, 'phone'),
            attributes: array_filter([
                'lead_type' => $lead->type,
                'lead_id' => $lead->id,
                'acquisition_slug' => $lead->acquisition?->slug,
                'service_slug' => $lead->service?->slug,
                'acquisition_path_key' => $lead->acquisition_path_key,
                'source_popup_key' => $lead->source_popup_key,
            ], fn (mixed $value): bool => filled($value)),
        );
    }

    private function marketingContactFromPopupLead(PopupLead $popupLead, ?AcquisitionContact $contact): ?MarketingContact
    {
        if (blank($popupLead->email)) {
            return null;
        }

        return new MarketingContact(
            acquisitionContactId: $contact?->id,
            email: $popupLead->email,
            name: $popupLead->name,
            phone: $popupLead->phone,
            attributes: array_filter([
                'popup_lead_id' => $popupLead->id,
                'popup_id' => $popupLead->popup_id,
                'lead_type' => $popupLead->lead_type,
                'popup_slug' => $popupLead->metadata['popup_slug'] ?? null,
                'acquisition_path_key' => $popupLead->metadata['acquisition_context']['acquisition_path_key'] ?? null,
                'source_popup_key' => $popupLead->metadata['acquisition_context']['source_popup_key'] ?? null,
            ], fn (mixed $value): bool => filled($value)),
        );
    }

    /**
     * @return array<int, string>
     */
    private function leadContextTagKeys(Lead $lead): array
    {
        return $this->uniqueTagKeys([
            'tag.lead.type.'.$lead->type,
            filled($lead->acquisition?->slug ?? null) ? 'tag.acquisition.'.$lead->acquisition->slug : null,
            filled($lead->service?->slug ?? null) ? 'tag.service.'.$lead->service->slug : null,
            filled($lead->acquisition_path_key) ? 'tag.acquisition_path.'.$lead->acquisition_path_key : null,
            filled($lead->source_popup_key) ? 'tag.source_popup.'.$lead->source_popup_key : null,
        ]);
    }

    /**
     * @param  array<int, string|null>  $tagKeys
     * @return array<int, string>
     */
    private function uniqueTagKeys(array $tagKeys): array
    {
        return collect($tagKeys)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->map(fn (string $value): string => Str::lower($value))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncContactAction(string $actionKey, MarketingContact $contact, array $payload): MarketingAction
    {
        return new MarketingAction(
            type: MarketingAction::TYPE_SYNC_CONTACT,
            actionKey: $actionKey,
            contact: $contact,
            audienceKey: $this->runtimeConfig->defaultMarketingAudienceKey(),
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function addToAudienceAction(string $actionKey, MarketingContact $contact, string $audienceKey, array $payload): ?MarketingAction
    {
        if (blank($audienceKey)) {
            return null;
        }

        return new MarketingAction(
            type: MarketingAction::TYPE_ADD_TO_AUDIENCE,
            actionKey: $actionKey,
            contact: $contact,
            audienceKey: $audienceKey,
            payload: $payload,
        );
    }

    /**
     * @param  array<int, string>  $tagKeys
     * @param  array<string, mixed>  $payload
     */
    private function applyTagsAction(string $actionKey, MarketingContact $contact, ?string $audienceKey, array $tagKeys, array $payload): ?MarketingAction
    {
        if (blank($audienceKey) || $tagKeys === []) {
            return null;
        }

        return new MarketingAction(
            type: MarketingAction::TYPE_APPLY_TAGS,
            actionKey: $actionKey,
            contact: $contact,
            audienceKey: $audienceKey,
            tagKeys: $tagKeys,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function triggerAutomationAction(string $actionKey, MarketingContact $contact, string $triggerKey, array $payload): MarketingAction
    {
        return new MarketingAction(
            type: MarketingAction::TYPE_TRIGGER_AUTOMATION,
            actionKey: $actionKey,
            contact: $contact,
            triggerKey: $triggerKey,
            payload: $payload,
        );
    }

    private function recordMarketingSyncState(MarketingAction $action, MarketingActionResult $result): void
    {
        if ($action->contact->acquisitionContactId === null) {
            return;
        }

        $audienceKey = $action->audienceKey ?: Arr::get($result->metadata, 'audience_key');

        MarketingContactSync::query()->updateOrCreate(
            [
                'acquisition_contact_id' => $action->contact->acquisitionContactId,
                'provider' => $result->provider,
                'audience_key' => $audienceKey,
            ],
            [
                'email' => $action->contact->email,
                'external_contact_id' => $result->externalContactId,
                'last_sync_status' => $result->successful
                    ? MarketingContactSync::STATUS_SYNCED
                    : MarketingContactSync::STATUS_FAILED,
                'last_error_message' => $result->errorMessage,
                'metadata' => array_filter([
                    'action_key' => $action->actionKey,
                    'trigger_key' => $action->triggerKey,
                    'tag_keys' => $action->tagKeys,
                    'provider_metadata' => $result->metadata,
                ], fn (mixed $value): bool => filled($value)),
                'last_synced_at' => $result->successful ? now() : null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function eventLogContext(CommunicationEvent $event, array $context = []): array
    {
        return array_filter([
            'communication_event_id' => $event->id,
            'event_key' => $event->event_key,
            'subject_type' => class_basename((string) $event->subject_type),
            'subject_id' => $event->subject_id,
            'acquisition_contact_id' => $event->acquisition_contact_id,
            'lead_id' => $this->subjectLogId($event, Lead::class),
            'popup_lead_id' => $this->subjectLogId($event, PopupLead::class),
            'status' => $event->status,
            ...$context,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function subjectLogId(CommunicationEvent $event, string $expectedType): ?int
    {
        return $event->subject_type === $expectedType ? (int) $event->subject_id : null;
    }

    private function maskEmail(?string $email): ?string
    {
        if (blank($email) || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);

        if ($local === '') {
            return null;
        }

        return Str::substr($local, 0, 1).'***@'.$domain;
    }
}
