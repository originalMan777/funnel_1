<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    private const SLOT_TYPE_MAP = [
        'home_intro' => LeadBox::TYPE_RESOURCE,
        'home_mid' => LeadBox::TYPE_SERVICE,
        'home_bottom' => LeadBox::TYPE_OFFER,
        'blog_index_mid_lead' => LeadBox::TYPE_OFFER,

        'blog_post_inline_1' => LeadBox::TYPE_OFFER,
        'blog_post_inline_2' => LeadBox::TYPE_OFFER,
        'blog_post_inline_3' => LeadBox::TYPE_OFFER,
        'blog_post_inline_4' => LeadBox::TYPE_OFFER,
        'blog_post_before_related' => LeadBox::TYPE_OFFER,
    ];

    private function requireLeadBoxAccess(Request $request): void
    {
        if (! $request->user()?->canManageLeadBoxes()) {
            abort(403);
        }
    }

    public function index(Request $request): Response
    {
        $this->requireLeadBoxAccess($request);

        $slots = collect(array_keys(self::SLOT_TYPE_MAP))
            ->map(function (string $slotKey) {
                $slot = LeadSlot::query()->firstOrCreate(
                    ['key' => $slotKey],
                    ['is_enabled' => true],
                );

                return [
                    'id' => $slot->id,
                    'key' => $slot->key,
                    'is_enabled' => $slot->is_enabled,
                    'required_type' => self::SLOT_TYPE_MAP[$slotKey],
                    'assignment_lead_box_id' => optional($slot->assignment)?->lead_box_id,
                ];
            })
            ->values();

        $activeResourceBoxes = LeadBox::query()
            ->where('type', LeadBox::TYPE_RESOURCE)
            ->where('status', LeadBox::STATUS_ACTIVE)
            ->orderBy('internal_name')
            ->get()
            ->map(fn (LeadBox $box) => [
                'id' => $box->id,
                'internal_name' => $box->internal_name,
                'title' => $box->title,
            ]);

        $activeServiceBoxes = LeadBox::query()
            ->where('type', LeadBox::TYPE_SERVICE)
            ->where('status', LeadBox::STATUS_ACTIVE)
            ->orderBy('internal_name')
            ->get()
            ->map(fn (LeadBox $box) => [
                'id' => $box->id,
                'internal_name' => $box->internal_name,
                'title' => $box->title,
            ]);

        $activeOfferBoxes = LeadBox::query()
            ->where('type', LeadBox::TYPE_OFFER)
            ->where('status', LeadBox::STATUS_ACTIVE)
            ->orderBy('internal_name')
            ->get()
            ->map(fn (LeadBox $box) => [
                'id' => $box->id,
                'internal_name' => $box->internal_name,
                'title' => $box->title,
            ]);

        return Inertia::render('Admin/LeadSlots/Index', [
            'slots' => $slots,
            'activeResourceBoxes' => $activeResourceBoxes,
            'activeServiceBoxes' => $activeServiceBoxes,
            'activeOfferBoxes' => $activeOfferBoxes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_box_id' => ['required', 'integer', Rule::exists('lead_boxes', 'id')],
            'lead_slot_key' => ['required', 'string', Rule::in(array_keys(self::SLOT_TYPE_MAP))],
            'first_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'message' => ['nullable', 'string'],
            'page_key' => ['nullable', 'string', 'max:255'],
        ]);

        $slot = LeadSlot::query()
            ->with('assignment')
            ->where('key', $validated['lead_slot_key'])
            ->first();

        if (! $slot || ! $slot->is_enabled) {
            return redirect()
                ->back()
                ->withErrors([
                    'lead_slot_key' => 'This lead slot is not available.',
                ])
                ->withInput();
        }

        $leadBox = LeadBox::query()->findOrFail((int) $validated['lead_box_id']);
        $requiredType = self::SLOT_TYPE_MAP[$slot->key];

        if ($leadBox->type !== $requiredType || $leadBox->status !== LeadBox::STATUS_ACTIVE) {
            return redirect()
                ->back()
                ->withErrors([
                    'lead_box_id' => 'This lead box is not available for that slot.',
                ])
                ->withInput();
        }

        if (! $slot->assignment || (int) $slot->assignment->lead_box_id !== (int) $leadBox->id) {
            return redirect()
                ->back()
                ->withErrors([
                    'lead_box_id' => 'This lead box is not assigned to that slot.',
                ])
                ->withInput();
        }

        if ($leadBox->type === LeadBox::TYPE_SERVICE && blank($validated['phone'] ?? null)) {
            return redirect()
                ->back()
                ->withErrors([
                    'phone' => 'Phone is required for this lead type.',
                ])
                ->withInput();
        }

        $pageKey = $validated['page_key'] ?? match (true) {
            str_starts_with($slot->key, 'home_') => 'home',
            str_starts_with($slot->key, 'blog_index_') => 'blog_index',
            str_starts_with($slot->key, 'blog_post_') => 'blog_show',
            default => null,
        };

        Lead::query()->create([
            'lead_box_id' => $leadBox->id,
            'lead_slot_key' => $slot->key,
            'page_key' => $pageKey,
            'type' => $leadBox->type,
            'first_name' => $validated['first_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'source_url' => $validated['source_url'] ?? null,
            'payload' => [
                'phone' => $validated['phone'] ?? null,
                'message' => $validated['message'] ?? null,
            ],
        ]);

        return redirect(route('home'))
            ->cookie('nojo_lead_captured', '1', 60 * 24 * 30)
            ->with('success', 'Thanks. We received your request.');
    }

    public function update(Request $request, LeadSlot $leadSlot): RedirectResponse
    {
        $this->requireLeadBoxAccess($request);

        abort_unless(array_key_exists($leadSlot->key, self::SLOT_TYPE_MAP), 404);

        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'lead_box_id' => ['nullable', 'integer', Rule::exists('lead_boxes', 'id')],
        ]);

        $leadSlot->update([
            'is_enabled' => (bool) $validated['is_enabled'],
        ]);

        $leadBoxId = $validated['lead_box_id'] ?? null;

        if ($leadBoxId === null) {
            LeadAssignment::query()->where('lead_slot_id', $leadSlot->id)->delete();

            return redirect()
                ->back()
                ->with('success', 'Slot updated.');
        }

        $leadBox = LeadBox::query()->findOrFail((int) $leadBoxId);
        $requiredType = self::SLOT_TYPE_MAP[$leadSlot->key];

        if ($leadBox->type !== $requiredType || $leadBox->status !== LeadBox::STATUS_ACTIVE) {
            $typeLabel = ucfirst($requiredType);

            return redirect()
                ->back()
                ->withErrors([
                    'lead_box_id' => "Only Active {$typeLabel} Lead Boxes can be assigned to this slot.",
                ]);
        }

        LeadAssignment::query()->updateOrCreate(
            ['lead_slot_id' => $leadSlot->id],
            ['lead_box_id' => $leadBox->id],
        );

        return redirect()
            ->back()
            ->with('success', 'Slot updated.');
    }
}
