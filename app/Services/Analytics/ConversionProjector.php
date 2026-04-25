<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Conversion;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;

class ConversionProjector
{
    public function __construct(
        private readonly AnalyticsCatalogService $catalogService,
        private readonly AnalyticsAttributionService $analyticsAttributionService,
    ) {}

    /**
     * Project business-owned conversion outcomes into analytics facts.
     */
    public function project(array $payload): Conversion
    {
        $conversion = Conversion::query()->create([
            'visitor_id' => $this->resolveId(Visitor::class, 'visitor_key', $payload['visitor_key'] ?? null),
            'session_id' => $this->resolveId(Session::class, 'session_key', $payload['session_key'] ?? null),
            'conversion_type_id' => $payload['conversion_type_id'],
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'lead_id' => $payload['lead_id'] ?? null,
            'popup_lead_id' => $payload['popup_lead_id'] ?? null,
            'page_id' => $this->resolveCatalogId('page', $payload['page_key'] ?? null),
            'cta_id' => $this->resolveCatalogId('cta', $payload['cta_key'] ?? null),
            'lead_box_id' => $payload['lead_box_id'] ?? null,
            'lead_slot_id' => $payload['lead_slot_id'] ?? null,
            'popup_id' => $payload['popup_id'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? now(),
            'properties' => $payload['properties'] ?? null,
        ]);

        $this->analyticsAttributionService->syncConversion($conversion);

        return $conversion;
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

    private function resolveCatalogId(string $catalog, mixed $value): ?int
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return match ($catalog) {
            'page' => $this->catalogService->ensurePage($value)->id,
            'cta' => $this->catalogService->ensureCta($value)->id,
            default => null,
        };
    }
}
