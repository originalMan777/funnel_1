<?php

namespace App\Services\Campaigns;

use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\CommunicationTemplate;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use App\Services\Communications\CommunicationTemplateRenderer;
use App\Services\Communications\CommunicationTemplateVariableResolver;
use App\Services\Communications\DTOs\TransactionalEmail;
use Illuminate\Support\Arr;

class CampaignStepSendService
{
    public const EVENT_KEY = 'campaign.step.sent';

    public function __construct(
        private readonly CampaignAudienceResolver $audienceResolver,
        private readonly TransactionalEmailProvider $transactionalEmailProvider,
        private readonly CommunicationTemplateVariableResolver $variableResolver,
        private readonly CommunicationTemplateRenderer $renderer,
    ) {}

    public function sendStep(CampaignEnrollment $enrollment, CampaignStep $step): void
    {
        $enrollment->loadMissing([
            'campaign',
            'lead',
            'popupLead',
            'acquisitionContact',
        ]);

        $recipient = $this->audienceResolver->resolve($enrollment);

        if ($recipient === null) {
            $enrollment->markFailed('recipient_unresolvable');

            return;
        }

        $message = $step->isTemplateMode()
            ? $this->resolveTemplateMessage($enrollment, $step, $recipient)
            : $this->resolveCustomMessage($step);

        if ($message === null) {
            $enrollment->markFailed('step_content_unavailable');

            return;
        }

        $event = CommunicationEvent::query()->create([
            'event_key' => self::EVENT_KEY,
            'subject_type' => $enrollment::class,
            'subject_id' => $enrollment->getKey(),
            'acquisition_contact_id' => $enrollment->acquisition_contact_id,
            'status' => CommunicationEvent::STATUS_PENDING,
            'payload' => $this->buildPayload($enrollment, $step, $recipient, $message),
        ]);

        $event->forceFill([
            'status' => CommunicationEvent::STATUS_PROCESSING,
            'processed_at' => null,
        ])->save();

        $email = new TransactionalEmail(
            actionKey: (string) $message['action_key'],
            toEmail: $recipient['email'],
            toName: $recipient['name'],
            subject: (string) $message['subject'],
            headline: (string) ($message['headline'] ?? ''),
            lines: [],
            payload: (array) ($event->payload ?? []),
            previewText: $message['preview_text'] ?? null,
            htmlBody: $message['html_body'] ?? null,
            textBody: $message['text_body'] ?? null,
            communicationTemplateId: $message['communication_template_id'] ?? null,
            communicationTemplateVersionId: $message['communication_template_version_id'] ?? null,
        );

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

        $event->forceFill([
            'status' => $result->successful
                ? CommunicationEvent::STATUS_PROCESSED
                : CommunicationEvent::STATUS_FAILED,
            'processed_at' => now(),
        ])->save();

        if (! $result->successful) {
            $enrollment->markFailed('delivery_failed');
        }
    }

    /**
     * @param  array{email: string, name: ?string, source_type: string, source_id: int}  $recipient
     * @return array<string, mixed>|null
     */
    private function resolveTemplateMessage(CampaignEnrollment $enrollment, CampaignStep $step, array $recipient): ?array
    {
        $template = $step->template()
            ->with('currentVersion')
            ->first();

        if (! $template || ! $this->isUsableTemplate($template)) {
            return null;
        }

        $version = $template->currentVersion;
        $variables = $this->buildVariables($enrollment, $step, $recipient, $template);
        $rendered = $this->renderer->render([
            'subject' => $version->subject,
            'preview_text' => $version->preview_text,
            'headline' => $version->headline,
            'html_body' => $version->html_body,
            'text_body' => $version->text_body,
        ], $variables);

        return [
            'action_key' => 'campaign.step.send',
            'subject' => $rendered['subject'],
            'headline' => $rendered['headline'],
            'preview_text' => $rendered['preview_text'],
            'html_body' => $rendered['html_body'],
            'text_body' => $rendered['text_body'],
            'communication_template_id' => $template->id,
            'communication_template_version_id' => $version->id,
            'variables' => $variables,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveCustomMessage(CampaignStep $step): ?array
    {
        if (blank($step->subject) || (blank($step->html_body) && blank($step->text_body))) {
            return null;
        }

        return [
            'action_key' => 'campaign.step.send',
            'subject' => (string) $step->subject,
            'headline' => null,
            'preview_text' => null,
            'html_body' => $step->html_body,
            'text_body' => $step->text_body,
            'communication_template_id' => null,
            'communication_template_version_id' => null,
            'variables' => [],
        ];
    }

    private function isUsableTemplate(CommunicationTemplate $template): bool
    {
        return $template->status === CommunicationTemplate::STATUS_ACTIVE
            && $template->currentVersion !== null
            && (bool) $template->currentVersion->is_published;
    }

    /**
     * @param  array{email: string, name: ?string, source_type: string, source_id: int}  $recipient
     * @return array<string, mixed>
     */
    private function buildPayload(CampaignEnrollment $enrollment, CampaignStep $step, array $recipient, array $message): array
    {
        return [
            'campaign' => [
                'id' => $enrollment->campaign?->id,
                'name' => $enrollment->campaign?->name,
                'status' => $enrollment->campaign?->status,
            ],
            'enrollment' => [
                'id' => $enrollment->id,
                'campaign_id' => $enrollment->campaign_id,
                'current_step_order' => $enrollment->current_step_order,
                'status' => $enrollment->status,
                'started_at' => $enrollment->started_at?->toIso8601String(),
            ],
            'step' => [
                'id' => $step->id,
                'step_order' => $step->step_order,
                'send_mode' => $step->send_mode,
                'delay_amount' => $step->delay_amount,
                'delay_unit' => $step->delay_unit,
            ],
            'recipient' => $recipient,
            'lead' => $enrollment->lead ? [
                'id' => $enrollment->lead->id,
                'type' => $enrollment->lead->type,
                'first_name' => $enrollment->lead->first_name,
                'email' => $enrollment->lead->email,
                'payload' => $enrollment->lead->payload ?? [],
            ] : null,
            'popup_lead' => $enrollment->popupLead ? [
                'id' => $enrollment->popupLead->id,
                'name' => $enrollment->popupLead->name,
                'email' => $enrollment->popupLead->email,
                'phone' => $enrollment->popupLead->phone,
                'message' => $enrollment->popupLead->message,
                'metadata' => $enrollment->popupLead->metadata ?? [],
            ] : null,
            'acquisition_contact' => $enrollment->acquisitionContact ? [
                'id' => $enrollment->acquisitionContact->id,
                'display_name' => $enrollment->acquisitionContact->display_name,
                'primary_email' => $enrollment->acquisitionContact->primary_email,
                'primary_phone' => $enrollment->acquisitionContact->primary_phone,
                'contact_type' => $enrollment->acquisitionContact->contact_type,
                'state' => $enrollment->acquisitionContact->state,
            ] : null,
            'message' => Arr::except($message, ['variables']),
            'template_variables' => $message['variables'] ?? [],
        ];
    }

    /**
     * @param  array{email: string, name: ?string, source_type: string, source_id: int}  $recipient
     * @return array<string, mixed>
     */
    private function buildVariables(
        CampaignEnrollment $enrollment,
        CampaignStep $step,
        array $recipient,
        CommunicationTemplate $template,
    ): array {
        return $this->variableResolver->resolve(
            samplePayload: [
                'campaign' => [
                    'id' => $enrollment->campaign?->id,
                    'name' => $enrollment->campaign?->name,
                    'status' => $enrollment->campaign?->status,
                ],
                'enrollment' => [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'current_step_order' => $enrollment->current_step_order,
                    'started_at' => $enrollment->started_at?->toIso8601String(),
                ],
                'step' => [
                    'id' => $step->id,
                    'order' => $step->step_order,
                    'send_mode' => $step->send_mode,
                ],
                'lead' => $enrollment->lead?->toArray(),
                'popup_lead' => $enrollment->popupLead?->toArray(),
                'acquisition_contact' => $enrollment->acquisitionContact?->toArray(),
            ],
            context: [
                'template' => [
                    'key' => $template->key,
                    'name' => $template->name,
                ],
                'recipient' => [
                    'email' => $recipient['email'],
                    'name' => $recipient['name'],
                    'source_type' => $recipient['source_type'],
                ],
            ],
        );
    }
}
