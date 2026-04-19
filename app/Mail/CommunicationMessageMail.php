<?php

namespace App\Mail;

use App\Services\Communications\DTOs\TransactionalEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommunicationMessageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly TransactionalEmail $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->email->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.communication-message',
            with: [
                'subject' => $this->email->subject,
                'previewText' => $this->email->previewText,
                'headline' => $this->email->headline,
                'lines' => $this->email->lines,
                'htmlBody' => $this->email->htmlBody,
                'textBody' => $this->email->textBody,
            ],
        );
    }
}
