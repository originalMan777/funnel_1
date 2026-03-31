<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeadSlotController extends Controller
{
    private const SLOT_TYPE_MAP = [
        'home_intro' => LeadBox::TYPE_RESOURCE,
        'home_mid' => LeadBox::TYPE_SERVICE,
        'home_bottom' => LeadBox::TYPE_OFFER,
        'blog_index_mid_lead' => LeadBox::TYPE_OFFER,
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
