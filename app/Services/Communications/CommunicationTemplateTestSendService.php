<?php

namespace App\Services\Communications;

use App\Models\CommunicationTemplate;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class CommunicationTemplateTestSendService
{
    public function __construct(
        private readonly CommunicationTemplateVariableResolver $variableResolver,
        private readonly CommunicationTemplateRenderer $renderer,
    ) {}

    public function sendTest(CommunicationTemplate $template, array $draftContent, array $samplePayload, string $toEmail, ?string $toName = null): void
    {
        $variables = $this->variableResolver->resolve($samplePayload, [
            'template' => [
                'key' => $template->key,
                'name' => $template->name,
            ],
        ]);

        $rendered = $this->renderer->render($draftContent, $variables);

        $mailable = new class($template, $rendered) extends Mailable
        {
            public function __construct(
                private readonly CommunicationTemplate $template,
                private readonly array $content,
            ) {}

            public function build(): static
            {
                $mail = $this->subject($this->content['subject'])
                    ->html($this->buildHtml());

                if (filled($this->template->from_email_override)) {
                    $mail->from(
                        $this->template->from_email_override,
                        $this->template->from_name_override
                    );
                }

                if (filled($this->template->reply_to_email)) {
                    $mail->replyTo($this->template->reply_to_email);
                }

                return $mail;
            }

            private function buildHtml(): string
            {
                $html = '';

                if (filled($this->content['preview_text'] ?? null)) {
                    $html .= '<div style="display:none;max-height:0;overflow:hidden;">'.e($this->content['preview_text']).'</div>';
                }

                if (filled($this->content['headline'] ?? null)) {
                    $html .= '<h1>'.e($this->content['headline']).'</h1>';
                }

                $html .= (string) ($this->content['html_body'] ?? '');

                if (filled($this->content['text_body'] ?? null)) {
                    $html .= '<pre style="white-space:pre-wrap;">'.e($this->content['text_body']).'</pre>';
                }

                return $html;
            }
        };

        $pendingMail = Mail::to($toEmail, $toName);

        $pendingMail->send($mailable);
    }
}
