<?php

namespace App\Services\Communications\DTOs;

class TransactionalEmail
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $lines
     */
    public function __construct(
        public readonly string $actionKey,
        public readonly string $toEmail,
        public readonly ?string $toName,
        public readonly string $subject,
        public readonly string $headline,
        public readonly array $lines,
        public readonly array $payload = [],
        public readonly ?string $previewText = null,
        public readonly ?string $htmlBody = null,
        public readonly ?string $textBody = null,
        public readonly ?int $communicationTemplateId = null,
        public readonly ?int $communicationTemplateVersionId = null,
    ) {}
}
