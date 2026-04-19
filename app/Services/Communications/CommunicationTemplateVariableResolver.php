<?php

namespace App\Services\Communications;

use Illuminate\Support\Arr;

class CommunicationTemplateVariableResolver
{
    public function resolve(array $samplePayload = [], array $context = []): array
    {
        $merged = array_replace_recursive($context, $samplePayload);

        return Arr::dot($merged);
    }
}
