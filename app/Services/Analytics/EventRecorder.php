<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class EventRecorder
{
    public function __construct(
        private readonly AnalyticsCatalogService $catalogService,
    ) {}

    /**
     * Persist a standardized analytics event row in the append-only event store.
     */
    public function record(array $payload): Event
    {
        $eventType = EventType::query()->where('event_key', $payload['event_key'])->first();

        if (! $eventType) {
            $registry = collect(config('analytics.events.default_types', []))
                ->firstWhere('event_key', $payload['event_key']);

            if (! is_array($registry)) {
                throw new InvalidArgumentException("Unknown analytics event key [{$payload['event_key']}].");
            }

            $eventType = EventType::query()->firstOrCreate(
                ['event_key' => $payload['event_key']],
                [
                    'label' => $registry['label'],
                    'category' => $registry['category'],
                ],
            );
        }

        $properties = Arr::where(
            Arr::only($payload['properties'] ?? [], ['component', 'variant', 'placement', 'value', 'step', 'status', 'trigger', 'source']),
            fn ($value) => $value !== null && $value !== '',
        );

        return Event::query()->create([
            'visitor_id' => $this->resolveId(Visitor::class, 'visitor_key', $payload['visitor_key'] ?? null),
            'session_id' => $this->resolveId(Session::class, 'session_key', $payload['session_key'] ?? null),
            'event_type_id' => $eventType->id,
            'page_id' => $this->resolveCatalogId('page', $payload['page_key'] ?? null),
            'cta_id' => $this->resolveCatalogId('cta', $payload['cta_key'] ?? null),
            'lead_box_id' => $payload['lead_box_id'] ?? null,
            'lead_slot_id' => $payload['lead_slot_id'] ?? null,
            'popup_id' => $payload['popup_id'] ?? null,
            'surface_id' => $this->resolveCatalogId('surface', $payload['surface_key'] ?? null),
            'subject_type' => $payload['subject_type'] ?? null,
            'subject_id' => $payload['subject_id'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? now(),
            'properties' => $properties === [] ? null : $properties,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, Event>
     */
    public function recordBatch(array $events): array
    {
        return array_map(fn (array $event) => $this->record($event), $events);
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
            'surface' => $this->catalogService->ensureSurface($value)->id,
            default => null,
        };
    }
}
