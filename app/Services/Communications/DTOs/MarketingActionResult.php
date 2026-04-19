<?php

namespace App\Services\Communications\DTOs;

class MarketingActionResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly bool $successful,
        public readonly string $provider,
        public readonly ?string $externalContactId = null,
        public readonly ?string $errorMessage = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function success(string $provider, ?string $externalContactId = null, array $metadata = []): self
    {
        return new self(true, $provider, $externalContactId, null, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function failure(string $provider, string $errorMessage, array $metadata = []): self
    {
        return new self(false, $provider, null, $errorMessage, $metadata);
    }
}
