<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsBootstrapService;
use App\Services\Analytics\EventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class IngestController extends Controller
{
    public function __construct(
        private readonly EventRecorder $eventRecorder,
        private readonly AnalyticsBootstrapService $analyticsBootstrapService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->analyticsBootstrapService->isReady()) {
            return response()->json([
                'accepted' => 0,
                'ready' => false,
            ], 503);
        }

        $validated = $request->validate([
            'visitor_key' => ['nullable', 'uuid'],
            'session_key' => ['nullable', 'uuid'],
            'events' => ['required', 'array', 'min:1', 'max:'.(int) config('analytics.ingest.batch_limit', 50)],
            'events.*.event_key' => ['required', 'string', 'max:100'],
            'events.*.occurred_at' => ['nullable', 'date'],
            'events.*.page_key' => ['nullable', 'string', 'max:100'],
            'events.*.cta_key' => ['nullable', 'string', 'max:100'],
            'events.*.surface_key' => ['nullable', 'string', 'max:100'],
            'events.*.lead_box_id' => ['nullable', 'integer', Rule::exists('lead_boxes', 'id')],
            'events.*.lead_slot_id' => ['nullable', 'integer', Rule::exists('lead_slots', 'id')],
            'events.*.popup_id' => ['nullable', 'integer', Rule::exists('popups', 'id')],
            'events.*.subject_type' => ['nullable', 'string', 'max:255'],
            'events.*.subject_id' => ['nullable', 'integer'],
            'events.*.properties' => ['nullable', 'array'],
        ]);

        try {
            $recorded = $this->eventRecorder->recordBatch(
                collect($validated['events'])
                    ->map(fn (array $event) => [
                        ...$event,
                        'visitor_key' => $event['visitor_key'] ?? $validated['visitor_key'] ?? null,
                        'session_key' => $event['session_key'] ?? $validated['session_key'] ?? null,
                    ])
                    ->all(),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'accepted' => count($recorded),
            'ready' => true,
        ], 202);
    }
}
