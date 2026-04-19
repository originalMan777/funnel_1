<?php

namespace App\Services\Communications\DTOs;

class MarketingAction
{
    public const TYPE_SYNC_CONTACT = 'sync_contact';

    public const TYPE_ADD_TO_AUDIENCE = 'add_to_audience';

    public const TYPE_APPLY_TAGS = 'apply_tags';

    public const TYPE_TRIGGER_AUTOMATION = 'trigger_automation';

    /**
     * @param  array<int, string>  $tagKeys
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $type,
        public readonly string $actionKey,
        public readonly MarketingContact $contact,
        public readonly ?string $audienceKey = null,
        public readonly array $tagKeys = [],
        public readonly ?string $triggerKey = null,
        public readonly array $payload = [],
    ) {}
}
