<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Services\Communications\CommunicationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CommunicationEventController extends Controller
{
    public function __construct(
        private readonly CommunicationService $communicationService,
    ) {}

    public function index(Request $request): Response
    {
        $status = trim((string) $request->string('status')->toString());
        $eventKey = trim((string) $request->string('event_key')->toString());
        $dateFrom = $this->normalizeDateFilter($request->string('date_from')->toString());
        $dateTo = $this->normalizeDateFilter($request->string('date_to')->toString());

        $events = CommunicationEvent::query()
            ->with('acquisitionContact:id,display_name,primary_email')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($eventKey !== '', fn ($query) => $query->where('event_key', 'like', '%'.$eventKey.'%'))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->toDateString()))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->toDateString()))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CommunicationEvent $event) => [
                'id' => $event->id,
                'event_key' => $event->event_key,
                'subject_type' => class_basename($event->subject_type),
                'subject_id' => $event->subject_id,
                'acquisition_contact' => $event->acquisitionContact ? [
                    'id' => $event->acquisitionContact->id,
                    'display_name' => $event->acquisitionContact->display_name,
                    'email' => $event->acquisitionContact->primary_email,
                ] : null,
                'status' => $event->status,
                'processed_at' => optional($event->processed_at)?->toISOString(),
                'created_at' => optional($event->created_at)?->toISOString(),
            ]);

        return Inertia::render('Admin/Communications/Events/Index', [
            'filters' => [
                'status' => $status,
                'event_key' => $eventKey,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'events' => $events,
        ]);
    }

    public function show(CommunicationEvent $communicationEvent): Response
    {
        $communicationEvent->load([
            'acquisitionContact:id,display_name,primary_email',
            'deliveries' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
        ]);

        return Inertia::render('Admin/Communications/Events/Show', [
            'event' => [
                'id' => $communicationEvent->id,
                'event_key' => $communicationEvent->event_key,
                'subject_type' => class_basename($communicationEvent->subject_type),
                'subject_id' => $communicationEvent->subject_id,
                'acquisition_contact' => $communicationEvent->acquisitionContact ? [
                    'id' => $communicationEvent->acquisitionContact->id,
                    'display_name' => $communicationEvent->acquisitionContact->display_name,
                    'email' => $communicationEvent->acquisitionContact->primary_email,
                ] : null,
                'status' => $communicationEvent->status,
                'payload' => $communicationEvent->payload ?? [],
                'created_at' => optional($communicationEvent->created_at)?->toISOString(),
                'processed_at' => optional($communicationEvent->processed_at)?->toISOString(),
                'can_requeue' => $this->canRequeue($communicationEvent),
            ],
            'deliveries' => $communicationEvent->deliveries->map(fn (CommunicationDelivery $delivery) => [
                'id' => $delivery->id,
                'action_key' => $delivery->action_key,
                'channel' => $delivery->channel,
                'provider' => $delivery->provider,
                'recipient_email' => $delivery->recipient_email,
                'recipient_name' => $delivery->recipient_name,
                'status' => $delivery->status,
                'error_message' => $delivery->error_message,
                'provider_message_id' => $delivery->provider_message_id,
                'created_at' => optional($delivery->created_at)?->toISOString(),
                'sent_at' => optional($delivery->sent_at)?->toISOString(),
            ])->values(),
        ]);
    }

    public function requeue(CommunicationEvent $communicationEvent): RedirectResponse
    {
        abort_unless($this->canRequeue($communicationEvent), 422);

        $this->communicationService->requeueEvent($communicationEvent);

        return back()->with('success', 'Communication event requeued.');
    }

    private function canRequeue(CommunicationEvent $communicationEvent): bool
    {
        return in_array($communicationEvent->status, [
            CommunicationEvent::STATUS_FAILED,
            CommunicationEvent::STATUS_PARTIAL_FAILURE,
        ], true);
    }

    private function normalizeDateFilter(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return '';
        }
    }
}
