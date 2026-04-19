<?php

namespace App\Services\Communications\DTOs;

class MarketingContact
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly ?int $acquisitionContactId,
        public readonly string $email,
        public readonly ?string $name,
        public readonly ?string $phone,
        public readonly array $attributes = [],
    ) {}
}
