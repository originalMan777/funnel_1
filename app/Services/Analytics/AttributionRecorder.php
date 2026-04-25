<?php

namespace App\Services\Analytics;

use App\Models\Analytics\AttributionTouch;
use App\Models\Analytics\Page;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;

class AttributionRecorder
{
    /**
     * Record observed source/referrer facts without inferring business ownership.
     */
    public function recordObservedTouch(array $payload): AttributionTouch
    {
        return AttributionTouch::query()->create([
            'visitor_id' => $this->resolveId(Visitor::class, 'visitor_key', $payload['visitor_key'] ?? null),
            'session_id' => $this->resolveId(Session::class, 'session_key', $payload['session_key'] ?? null),
            'landing_page_id' => $this->resolveId(Page::class, 'page_key', $payload['landing_page_key'] ?? null),
            'landing_url' => $payload['landing_url'] ?? null,
            'referrer_url' => $payload['referrer_url'] ?? null,
            'referrer_host' => $payload['referrer_host'] ?? null,
            'utm_source' => $payload['utm_source'] ?? null,
            'utm_medium' => $payload['utm_medium'] ?? null,
            'utm_campaign' => $payload['utm_campaign'] ?? null,
            'utm_term' => $payload['utm_term'] ?? null,
            'utm_content' => $payload['utm_content'] ?? null,
            'attribution_method' => $payload['attribution_method'] ?? 'observed',
            'attribution_confidence' => $payload['attribution_confidence'] ?? 1,
            'occurred_at' => $payload['occurred_at'] ?? now(),
        ]);
    }

    private function resolveId(string $modelClass, string $column, mixed $value): ?int
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $modelClass::query()
            ->where($column, $value)
            ->value('id');
    }
}
