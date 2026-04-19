<?php

namespace App\Services\Communications\DTOs;

class TransactionalSendResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $provider,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function success(string $provider, ?string $providerMessageId = null): self
    {
        return new self(true, $provider, $providerMessageId);
    }

    public static function failure(string $provider, string $errorMessage): self
    {
        return new self(false, $provider, null, $errorMessage);
    }
}
