<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationDelivery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CommunicationDeliveryController extends Controller
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->string('status')->toString());
        $provider = trim((string) $request->string('provider')->toString());
        $channel = trim((string) $request->string('channel')->toString());
        $recipient = trim((string) $request->string('recipient')->toString());
        $eventKey = trim((string) $request->string('event_key')->toString());
        $dateFrom = $this->normalizeDateFilter($request->string('date_from')->toString());
        $dateTo = $this->normalizeDateFilter($request->string('date_to')->toString());

        $deliveries = CommunicationDelivery::query()
            ->with('communicationEvent:id,event_key')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($provider !== '', fn ($query) => $query->where('provider', $provider))
            ->when($channel !== '', fn ($query) => $query->where('channel', $channel))
            ->when($recipient !== '', fn ($query) => $query->where('recipient_email', 'like', '%'.$recipient.'%'))
            ->when($eventKey !== '', fn ($query) => $query->whereHas('communicationEvent', fn ($eventQuery) => $eventQuery->where('event_key', 'like', '%'.$eventKey.'%')))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->toDateString()))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->toDateString()))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CommunicationDelivery $delivery) => [
                'id' => $delivery->id,
                'communication_event_id' => $delivery->communication_event_id,
                'event_key' => $delivery->communicationEvent?->event_key,
                'action_key' => $delivery->action_key,
                'channel' => $delivery->channel,
                'provider' => $delivery->provider,
                'recipient_email' => $delivery->recipient_email,
                'recipient_name' => $delivery->recipient_name,
                'status' => $delivery->status,
                'error_message' => $delivery->error_message,
                'subject' => $delivery->subject,
                'created_at' => optional($delivery->created_at)?->toISOString(),
                'sent_at' => optional($delivery->sent_at)?->toISOString(),
            ]);

        return Inertia::render('Admin/Communications/Deliveries/Index', [
            'filters' => [
                'status' => $status,
                'provider' => $provider,
                'channel' => $channel,
                'recipient' => $recipient,
                'event_key' => $eventKey,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'deliveries' => $deliveries,
        ]);
    }

    public function show(CommunicationDelivery $communicationDelivery): Response
    {
        $communicationDelivery->load('communicationEvent:id,event_key,status');

        return Inertia::render('Admin/Communications/Deliveries/Show', [
            'delivery' => [
                'id' => $communicationDelivery->id,
                'event' => $communicationDelivery->communicationEvent ? [
                    'id' => $communicationDelivery->communicationEvent->id,
                    'event_key' => $communicationDelivery->communicationEvent->event_key,
                    'status' => $communicationDelivery->communicationEvent->status,
                ] : null,
                'action_key' => $communicationDelivery->action_key,
                'channel' => $communicationDelivery->channel,
                'provider' => $communicationDelivery->provider,
                'recipient_email' => $communicationDelivery->recipient_email,
                'recipient_name' => $communicationDelivery->recipient_name,
                'subject' => $communicationDelivery->subject,
                'status' => $communicationDelivery->status,
                'error_message' => $communicationDelivery->error_message,
                'provider_message_id' => $communicationDelivery->provider_message_id,
                'payload' => $communicationDelivery->payload ?? [],
                'created_at' => optional($communicationDelivery->created_at)?->toISOString(),
                'updated_at' => optional($communicationDelivery->updated_at)?->toISOString(),
                'sent_at' => optional($communicationDelivery->sent_at)?->toISOString(),
            ],
        ]);
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
