<?php

namespace App\Services\Communications;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class CommunicationComposerService
{
    public function __construct(
        private readonly CommunicationTemplateVariableResolver $variableResolver,
        private readonly CommunicationTemplateRenderer $renderer,
        private readonly CommunicationRuntimeConfig $runtimeConfig,
    ) {}

    /**
     * @param  array<string, mixed>  $draftContent
     * @param  array<string, mixed>  $samplePayload
     * @return array<string, string|null>
     */
    public function render(array $draftContent, array $samplePayload = []): array
    {
        $variables = $this->variableResolver->resolve($samplePayload);

        $rendered = $this->renderer->render([
            'subject' => $draftContent['subject'] ?? '',
            'preview_text' => $draftContent['preview_text'] ?? null,
            'headline' => $draftContent['headline'] ?? null,
            'html_body' => '',
            'text_body' => $draftContent['message'] ?? '',
        ], $variables);

        $message = $rendered['text_body'] ?? '';

        return [
            'subject' => $rendered['subject'],
            'preview_text' => $rendered['preview_text'],
            'headline' => $rendered['headline'],
            'html_body' => $this->formatMessageHtml($message),
            'text_body' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $draftContent
     * @param  array<string, mixed>  $samplePayload
     */
    public function send(
        array $draftContent,
        array $samplePayload,
        string $toEmail,
        ?string $toName,
        string $fromEmail,
        ?string $fromName,
    ): void {
        $rendered = $this->render($draftContent, $samplePayload);

        $mailable = new class($rendered, $fromEmail, $fromName) extends Mailable
        {
            /**
             * @param  array<string, string|null>  $content
             */
            public function __construct(
                private readonly array $content,
                private readonly string $fromEmail,
                private readonly ?string $fromName,
            ) {}

            public function build(): static
            {
                return $this->from($this->fromEmail, $this->fromName)
                    ->subject((string) $this->content['subject'])
                    ->view('emails.communication-message', [
                        'subject' => $this->content['subject'],
                        'previewText' => $this->content['preview_text'],
                        'headline' => $this->content['headline'],
                        'lines' => [],
                        'htmlBody' => $this->content['html_body'],
                        'textBody' => $this->content['text_body'],
                    ]);
            }
        };

        Mail::mailer($this->mailer())
            ->to($toEmail, $toName)
            ->send($mailable);
    }

    private function mailer(): string
    {
        return match ($this->runtimeConfig->transactionalProvider()) {
            'postmark' => (string) config('communications.postmark.mailer', 'postmark'),
            default => (string) config('communications.log.mailer', 'log'),
        };
    }

    private function formatMessageHtml(?string $message): string
    {
        $message = (string) ($message ?? '');

        $paragraphs = preg_split("/(?:\r\n|\r|\n){2,}/", trim($message)) ?: [];

        if ($paragraphs === []) {
            return '';
        }

        return collect($paragraphs)
            ->map(function (string $paragraph): string {
                $paragraph = trim($paragraph);

                if ($paragraph === '') {
                    return '';
                }

                return '<p>'.nl2br(e($paragraph), false).'</p>';
            })
            ->filter()
            ->implode('');
    }
}
