<?php

namespace App\Services\Communications;

use App\Models\CommunicationEvent;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationTemplateBinding;
use App\Services\Communications\DTOs\TransactionalEmail;

class CommunicationTemplateRuntimeResolver
{
    public function __construct(
        private readonly CommunicationTemplateVariableResolver $variableResolver,
        private readonly CommunicationTemplateRenderer $renderer,
    ) {}

    public function resolveForEvent(CommunicationEvent $event, TransactionalEmail $email): ?CommunicationTemplateBoundMessageData
    {
        $binding = CommunicationTemplateBinding::query()
            ->with([
                'template',
                'template.currentVersion',
            ])
            ->where('event_key', $event->event_key)
            ->where('channel', CommunicationTemplate::CHANNEL_EMAIL)
            ->where('is_enabled', true)
            ->where('action_key', $email->actionKey)
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->first(fn (CommunicationTemplateBinding $binding): bool => $this->isEligible($binding));

        if (! $binding) {
            return null;
        }

        $template = $binding->template;
        $version = $template->currentVersion;

        $variables = $this->variableResolver->resolve(
            (array) ($event->payload ?? []),
            [
                'event' => [
                    'id' => $event->id,
                    'key' => $event->event_key,
                ],
                'action' => [
                    'key' => $email->actionKey,
                ],
                'template' => [
                    'key' => $template->key,
                    'name' => $template->name,
                ],
                'recipient' => [
                    'email' => $email->toEmail,
                    'name' => $email->toName,
                ],
            ],
        );

        $rendered = $this->renderer->render([
            'subject' => $version->subject,
            'preview_text' => $version->preview_text,
            'headline' => $version->headline,
            'html_body' => $version->html_body,
            'text_body' => $version->text_body,
        ], $variables);

        return new CommunicationTemplateBoundMessageData(
            templateId: $template->id,
            templateVersionId: $version->id,
            actionKey: $email->actionKey,
            subject: $rendered['subject'],
            previewText: $rendered['preview_text'],
            headline: $rendered['headline'],
            htmlBody: $rendered['html_body'],
            textBody: $rendered['text_body'],
            variables: $variables,
        );
    }

    private function isEligible(CommunicationTemplateBinding $binding): bool
    {
        $template = $binding->template;

        if (! $template) {
            return false;
        }

        if ($template->status !== CommunicationTemplate::STATUS_ACTIVE) {
            return false;
        }

        if ($template->current_version_id === null) {
            return false;
        }

        if (! $template->currentVersion) {
            return false;
        }

        return (bool) $template->currentVersion->is_published;
    }
}
