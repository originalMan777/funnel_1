<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeadController extends Controller
{
    private const SLOT_TYPE_MAP = [
        'home_intro' => LeadBox::TYPE_RESOURCE,
        'home_mid' => LeadBox::TYPE_SERVICE,
    ];

    public function store(Request $request): RedirectResponse
    {
        $validatedBase = $request->validate([
            'lead_box_id' => ['required', 'integer', Rule::exists('lead_boxes', 'id')],
            'lead_slot_key' => ['required', 'string', Rule::in(array_keys(self::SLOT_TYPE_MAP))],
            'page_key' => ['nullable', 'string', 'max:64'],
            'source_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $slot = LeadSlot::query()->where('key', $validatedBase['lead_slot_key'])->first();

        if (! $slot || ! $slot->is_enabled) {
            throw ValidationException::withMessages([
                'lead_slot_key' => 'This lead slot is not available.',
            ]);
        }

        $assignment = LeadAssignment::query()->where('lead_slot_id', $slot->id)->first();

        if (! $assignment || (int) $assignment->lead_box_id !== (int) $validatedBase['lead_box_id']) {
            throw ValidationException::withMessages([
                'lead_box_id' => 'This lead box is not assigned to the requested slot.',
            ]);
        }

        $leadBox = LeadBox::query()->findOrFail((int) $validatedBase['lead_box_id']);

        $requiredType = self::SLOT_TYPE_MAP[$slot->key] ?? null;

        if (! $requiredType || $leadBox->type !== $requiredType || $leadBox->status !== LeadBox::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'lead_box_id' => 'This lead box is not currently available.',
            ]);
        }

        $rules = [
            'first_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
        ];

        if ($leadBox->type === LeadBox::TYPE_SERVICE) {
            $rules['phone'] = ['required', 'string', 'max:80'];
            $rules['message'] = ['nullable', 'string', 'max:2000'];
        }

        $validatedFields = $request->validate($rules);

        $sourceUrl = $validatedBase['source_url'] ?: ($request->headers->get('referer') ?: $request->fullUrl());

        $payload = [
            'first_name' => $validatedFields['first_name'],
            'email' => $validatedFields['email'],
        ];

        if ($leadBox->type === LeadBox::TYPE_SERVICE) {
            $payload['phone'] = $validatedFields['phone'];
            $payload['message'] = $validatedFields['message'] ?? null;
        }

        Lead::create([
            'lead_box_id' => $leadBox->id,
            'lead_slot_key' => $slot->key,
            'page_key' => $validatedBase['page_key'] ?? null,
            'source_url' => $sourceUrl,
            'type' => $leadBox->type,
            'first_name' => $validatedFields['first_name'],
            'email' => $validatedFields['email'],
            'payload' => $payload,
        ]);

        $cookie = Cookie::make('nojo_lead_captured', '1', 60 * 24 * 90);

        return redirect()
            ->back()
            ->withCookie($cookie)
            ->with('success', 'Thanks! We received your info.');
    }

    public function storeConsultation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        Lead::create([
            'lead_box_id' => null,
            'lead_slot_key' => null,
            'page_key' => 'consultation_request',
            'source_url' => $request->fullUrl(),
            'type' => 'consultation',
            'first_name' => $validated['name'],
            'email' => $validated['email'],
            'payload' => [
                'phone' => $validated['phone'] ?? null,
                'message' => $validated['message'] ?? null,
            ],
        ]);

        return redirect()->back()->with('success', 'Consultation request submitted.');
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Lead::create([
            'lead_box_id' => null,
            'lead_slot_key' => null,
            'page_key' => 'contact',
            'source_url' => $request->fullUrl(),
            'type' => 'contact',
            'first_name' => $validated['name'],
            'email' => $validated['email'],
            'payload' => [
                'message' => $validated['message'],
            ],
        ]);

        return redirect()->back()->with('success', 'Message sent.');
    }
}
