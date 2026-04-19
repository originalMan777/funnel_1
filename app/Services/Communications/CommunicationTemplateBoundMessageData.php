<?php

namespace App\Services\Communications;

class CommunicationTemplateBoundMessageData
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function __construct(
        public readonly int $templateId,
        public readonly int $templateVersionId,
        public readonly string $actionKey,
        public readonly string $subject,
        public readonly ?string $previewText,
        public readonly ?string $headline,
        public readonly string $htmlBody,
        public readonly ?string $textBody,
        public readonly array $variables = [],
    ) {}
}
