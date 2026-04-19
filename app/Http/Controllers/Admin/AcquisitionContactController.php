<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcquisitionContact;
use App\Models\AcquisitionEvent;
use App\Models\AcquisitionSource;
use App\Models\AcquisitionTouch;
use App\Models\Lead;
use App\Models\PopupLead;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcquisitionContactController extends Controller
{
    private const ALLOWED_STATES = [
        'new',
        'contacted',
        'engaged',
        'qualified',
        'converted',
        'lost',
        'suppressed',
    ];

    private const ALLOWED_TOUCH_TYPES = [
        'call',
        'email',
        'note',
        'follow_up',
    ];

    private const ALLOWED_TOUCH_STATUSES = [
        'completed',
        'scheduled',
    ];

    public function index(): Response
    {
        $contacts = AcquisitionContact::query()
            ->select([
                'id',
                'display_name',
                'primary_email',
                'primary_phone',
                'state',
                'source_type',
                'source_label',
                'last_activity_at',
                'created_at',
            ])
            ->orderByRaw('COALESCE(last_activity_at, created_at) DESC')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->through(fn (AcquisitionContact $contact) => [
                'id' => $contact->id,
                'display_name' => $contact->display_name,
                'email' => $contact->primary_email,
                'phone' => $contact->primary_phone,
                'state' => $contact->state,
                'source_type' => $contact->source_type,
                'source_label' => $contact->source_label,
                'last_activity_at' => optional($contact->last_activity_at)?->toISOString(),
                'created_at' => optional($contact->created_at)?->toISOString(),
            ]);

        return Inertia::render('Admin/Acquisition/Contacts/Index', [
            'contacts' => $contacts,
        ]);
    }

    public function updateState(Request $request, AcquisitionContact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'state' => ['required', 'string', Rule::in(self::ALLOWED_STATES)],
        ]);

        $fromState = $contact->state;
        $toState = $validated['state'];
        $occurredAt = now();

        $contact->state = $toState;
        $contact->last_activity_at = $occurredAt;

        if ($toState === 'qualified' && $contact->qualified_at === null) {
            $contact->qualified_at = $occurredAt;
        }

        if ($toState === 'converted' && $contact->converted_at === null) {
            $contact->converted_at = $occurredAt;
        }

        $contact->save();

        AcquisitionEvent::query()->create([
            'acquisition_contact_id' => $contact->id,
            'acquisition_company_id' => $contact->acquisition_company_id,
            'acquisition_person_id' => $contact->acquisition_person_id,
            'event_type' => 'state_changed',
            'actor_type' => 'user',
            'actor_user_id' => $request->user()?->id,
            'summary' => 'State changed',
            'details' => [
                'from_state' => $fromState,
                'to_state' => $toState,
            ],
            'occurred_at' => $occurredAt,
        ]);

        return to_route('admin.acquisition.contacts.show', $contact);
    }

    public function storeTouch(Request $request, AcquisitionContact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(self::ALLOWED_TOUCH_TYPES)],
            'status' => ['required', 'string', Rule::in(self::ALLOWED_TOUCH_STATUSES)],
            'summary' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
            'scheduled_for' => ['nullable', 'date', 'required_if:status,scheduled'],
        ]);

        $occurredAt = now();
        $scheduledFor = $validated['status'] === 'scheduled'
            ? Carbon::parse($validated['scheduled_for'])
            : $occurredAt->copy();

        $touch = AcquisitionTouch::query()->create([
            'acquisition_contact_id' => $contact->id,
            'owner_user_id' => $request->user()?->id,
            'touch_type' => $validated['type'],
            'status' => $validated['status'],
            'scheduled_for' => $scheduledFor,
            'completed_at' => $validated['status'] === 'completed' ? $occurredAt : null,
            'subject' => $validated['summary'],
            'body' => $validated['details'] ?? null,
            'recipient_email' => $contact->primary_email,
            'recipient_phone' => $contact->primary_phone,
            'metadata' => [
                'manual' => true,
            ],
        ]);

        $contact->last_activity_at = $occurredAt;

        if (
            $validated['status'] === 'scheduled'
            && ($contact->next_action_at === null || $scheduledFor->lt($contact->next_action_at))
        ) {
            $contact->next_action_at = $scheduledFor;
        }

        $contact->save();

        AcquisitionEvent::query()->create([
            'acquisition_contact_id' => $contact->id,
            'acquisition_company_id' => $contact->acquisition_company_id,
            'acquisition_person_id' => $contact->acquisition_person_id,
            'event_type' => 'touch_logged',
            'actor_type' => 'user',
            'actor_user_id' => $request->user()?->id,
            'related_table' => 'acquisition_touches',
            'related_id' => $touch->id,
            'summary' => $validated['summary'],
            'details' => array_filter([
                'touch_type' => $validated['type'],
                'touch_status' => $validated['status'],
                'scheduled_for' => $validated['status'] === 'scheduled' ? $scheduledFor->toISOString() : null,
                'touch_details' => $validated['details'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''),
            'occurred_at' => $occurredAt,
        ]);

        return to_route('admin.acquisition.contacts.show', $contact);
    }

    public function show(AcquisitionContact $contact): Response
    {
        $contact->load([
            'company:id,name',
            'person:id,full_name,email,phone',
            'sources' => fn ($query) => $query->latest('created_at'),
            'events' => fn ($query) => $query->latest('occurred_at'),
        ]);

        $leads = Lead::query()
            ->where('acquisition_contact_id', $contact->id)
            ->latest('created_at')
            ->get();

        $popupLeads = PopupLead::query()
            ->where('acquisition_contact_id', $contact->id)
            ->latest('created_at')
            ->get();

        return Inertia::render('Admin/Acquisition/Contacts/Show', [
            'contact' => [
                'id' => $contact->id,
                'display_name' => $contact->display_name,
                'email' => $contact->primary_email,
                'phone' => $contact->primary_phone,
                'state' => $contact->state,
                'contact_type' => $contact->contact_type,
                'source_type' => $contact->source_type,
                'source_label' => $contact->source_label,
                'company_name' => $contact->company?->name ?? $contact->company_name_snapshot,
                'person_name' => $contact->person?->full_name,
                'created_at' => optional($contact->created_at)?->toISOString(),
                'last_activity_at' => optional($contact->last_activity_at)?->toISOString(),
            ],
            'timeline_items' => $this->buildTimelineItems($contact, $leads, $popupLeads),
        ]);
    }

    private function buildTimelineItems(
        AcquisitionContact $contact,
        Collection $leads,
        Collection $popupLeads
    ): array {
        $items = collect([
            [
                'type' => 'contact_created',
                'title' => 'Contact created',
                'subtitle' => $contact->display_name ?: $contact->primary_email ?: 'Acquisition contact',
                'timestamp' => optional($contact->created_at)?->toISOString(),
                'details' => array_filter([
                    'contact_type' => $contact->contact_type,
                    'source_type' => $contact->source_type,
                    'source_label' => $contact->source_label,
                    'email' => $contact->primary_email,
                    'phone' => $contact->primary_phone,
                    'state' => $contact->state,
                ], fn ($value) => $value !== null && $value !== ''),
                '_sort_timestamp' => optional($contact->created_at)?->getTimestamp() ?? 0,
            ],
        ]);

        $items = $items
            ->concat($contact->events->map(fn (AcquisitionEvent $event) => [
                'type' => $this->normalizeTimelineType($event->event_type),
                'title' => $event->summary ?: $this->headlineForType($event->event_type),
                'subtitle' => $this->subtitleForEvent($event),
                'timestamp' => optional($event->occurred_at)?->toISOString(),
                'details' => array_filter([
                    'event_type' => $event->event_type,
                    'channel' => $event->channel,
                    'actor_type' => $event->actor_type,
                    'related_table' => $event->related_table,
                    'related_id' => $event->related_id,
                    ...($event->details ?? []),
                ], fn ($value) => $value !== null && $value !== ''),
                '_sort_timestamp' => optional($event->occurred_at)?->getTimestamp() ?? 0,
            ]))
            ->concat($contact->sources->map(fn (AcquisitionSource $source) => [
                'type' => 'source_recorded',
                'title' => 'Source recorded',
                'subtitle' => $source->source_type ?: 'Acquisition source',
                'timestamp' => optional($source->created_at)?->toISOString(),
                'details' => array_filter([
                    'source_type' => $source->source_type,
                    'source_table' => $source->source_table,
                    'source_record_id' => $source->source_record_id,
                    'page_key' => $source->page_key,
                    'source_url' => $source->source_url,
                    'utm_source' => $source->utm_source,
                    'utm_medium' => $source->utm_medium,
                    'utm_campaign' => $source->utm_campaign,
                    ...($source->metadata ?? []),
                ], fn ($value) => $value !== null && $value !== ''),
                '_sort_timestamp' => optional($source->created_at)?->getTimestamp() ?? 0,
            ]))
            ->concat($leads->map(fn (Lead $lead) => [
                'type' => 'lead_submission',
                'title' => 'Lead submitted',
                'subtitle' => $lead->type ?: 'Lead form',
                'timestamp' => optional($lead->created_at)?->toISOString(),
                'details' => array_filter([
                    'lead_id' => $lead->id,
                    'type' => $lead->type,
                    'page_key' => $lead->page_key,
                    'lead_slot_key' => $lead->lead_slot_key,
                    'lead_box_id' => $lead->lead_box_id,
                    'source_url' => $lead->source_url,
                    'first_name' => $lead->first_name,
                    'email' => $lead->email,
                    ...($lead->payload ?? []),
                ], fn ($value) => $value !== null && $value !== ''),
                '_sort_timestamp' => optional($lead->created_at)?->getTimestamp() ?? 0,
            ]))
            ->concat($popupLeads->map(fn (PopupLead $popupLead) => [
                'type' => 'popup_submission',
                'title' => 'Popup submitted',
                'subtitle' => $popupLead->lead_type ?: 'Popup lead',
                'timestamp' => optional($popupLead->created_at)?->toISOString(),
                'details' => array_filter([
                    'popup_lead_id' => $popupLead->id,
                    'popup_id' => $popupLead->popup_id,
                    'lead_type' => $popupLead->lead_type,
                    'page_key' => $popupLead->page_key,
                    'source_url' => $popupLead->source_url,
                    'name' => $popupLead->name,
                    'email' => $popupLead->email,
                    'phone' => $popupLead->phone,
                    'message' => $popupLead->message,
                    ...($popupLead->metadata ?? []),
                ], fn ($value) => $value !== null && $value !== ''),
                '_sort_timestamp' => optional($popupLead->created_at)?->getTimestamp() ?? 0,
            ]));

        return $items
            ->sortByDesc('_sort_timestamp')
            ->values()
            ->map(function (array $item) {
                unset($item['_sort_timestamp']);

                return $item;
            })
            ->all();
    }

    private function normalizeTimelineType(?string $type): string
    {
        return match ($type) {
            'contact_created', 'lead_submission', 'popup_submission', 'source_recorded', 'state_changed', 'touch_logged' => $type,
            default => 'source_recorded',
        };
    }

    private function headlineForType(?string $type): string
    {
        return match ($type) {
            'lead_submission' => 'Lead submission received',
            'popup_submission' => 'Popup submission received',
            'contact_created' => 'Contact created',
            'state_changed' => 'State changed',
            'touch_logged' => 'Touch logged',
            default => 'Acquisition activity recorded',
        };
    }

    private function subtitleForEvent(AcquisitionEvent $event): string
    {
        if ($event->event_type === 'touch_logged') {
            $touchType = $event->details['touch_type'] ?? null;
            $touchStatus = $event->details['touch_status'] ?? null;
            $parts = array_filter([
                $touchType ? $this->formatTimelineValue($touchType) : null,
                $touchStatus ? $this->formatTimelineValue($touchStatus) : null,
            ]);

            return $parts !== [] ? implode(' • ', $parts) : 'Manual touch';
        }

        if ($event->event_type === 'state_changed') {
            $fromState = $event->details['from_state'] ?? null;
            $toState = $event->details['to_state'] ?? null;

            if ($fromState && $toState) {
                return $this->formatTimelineValue($fromState) . ' -> ' . $this->formatTimelineValue($toState);
            }
        }

        return $event->channel ? 'Channel: ' . $event->channel : 'Acquisition event';
    }

    private function formatTimelineValue(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
