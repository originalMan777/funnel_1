<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OfferLeadBoxController extends Controller
{
    private function requireLeadBoxAccess(Request $request): void
    {
        if (! $request->user()?->canManageLeadBoxes()) {
            abort(403);
        }
    }

    public function create(Request $request): Response
    {
        $this->requireLeadBoxAccess($request);

        return Inertia::render('Admin/LeadBoxes/Offer/Edit', [
            'mode' => 'create',
            'leadBox' => null,
            'statuses' => config('lead_blocks.statuses'),
            'icons' => config('lead_blocks.icons'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireLeadBoxAccess($request);

        $validated = $this->validatePayload($request);

        $leadBox = LeadBox::create([
            'type' => LeadBox::TYPE_OFFER,
            'status' => $validated['status'],
            'internal_name' => $validated['internal_name'],
            'title' => $validated['title'],
            'short_text' => $validated['breakdown_line_1'],
            'button_text' => $validated['button_text'] ?? null,
            'icon_key' => null,
            'content' => [
                'breakdown_line_2' => $validated['breakdown_line_2'],
                'cta_line' => $validated['cta_line'],
                'reassurance_text' => $validated['reassurance_text'] ?? null,
                'value_points' => $validated['value_points'],
            ],
            'settings' => [],
        ]);

        return redirect()
            ->route('admin.lead-boxes.offer.edit', $leadBox)
            ->with('success', 'Offer Lead Box created.');
    }

    public function edit(Request $request, LeadBox $leadBox): Response
    {
        $this->requireLeadBoxAccess($request);

        abort_unless($leadBox->type === LeadBox::TYPE_OFFER, 404);

        $content = $leadBox->content ?? [];

        return Inertia::render('Admin/LeadBoxes/Offer/Edit', [
            'mode' => 'edit',
            'leadBox' => [
                'id' => $leadBox->id,
                'type' => $leadBox->type,
                'status' => $leadBox->status,
                'internal_name' => $leadBox->internal_name,
                'title' => $leadBox->title,
                'short_text' => $leadBox->short_text,
                'button_text' => $leadBox->button_text,
                'breakdown_line_1' => $leadBox->short_text,
                'breakdown_line_2' => $content['breakdown_line_2'] ?? '',
                'cta_line' => $content['cta_line'] ?? '',
                'reassurance_text' => $content['reassurance_text'] ?? null,
                'value_points' => $content['value_points'] ?? [
                    ['icon_key' => 'shield-check', 'line' => 'Clear value'],
                    ['icon_key' => 'clock', 'line' => 'Fast next step'],
                    ['icon_key' => 'message-square', 'line' => 'Simple decision'],
                ],
            ],
            'statuses' => config('lead_blocks.statuses'),
            'icons' => config('lead_blocks.icons'),
        ]);
    }

    public function update(Request $request, LeadBox $leadBox): RedirectResponse
    {
        $this->requireLeadBoxAccess($request);

        abort_unless($leadBox->type === LeadBox::TYPE_OFFER, 404);

        $validated = $this->validatePayload($request);

        $leadBox->update([
            'status' => $validated['status'],
            'internal_name' => $validated['internal_name'],
            'title' => $validated['title'],
            'short_text' => $validated['breakdown_line_1'],
            'button_text' => $validated['button_text'] ?? null,
            'content' => [
                'breakdown_line_2' => $validated['breakdown_line_2'],
                'cta_line' => $validated['cta_line'],
                'reassurance_text' => $validated['reassurance_text'] ?? null,
                'value_points' => $validated['value_points'],
            ],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Offer Lead Box updated.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'status' => ['required', Rule::in(config('lead_blocks.statuses'))],
            'internal_name' => ['required', 'string', 'max:160'],
            'title' => ['required', 'string', 'max:200'],
            'breakdown_line_1' => ['required', 'string', 'max:220'],
            'breakdown_line_2' => ['required', 'string', 'max:220'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'cta_line' => ['required', 'string', 'max:220'],
            'reassurance_text' => ['nullable', 'string', 'max:220'],
            'value_points' => ['required', 'array', 'size:3'],
            'value_points.*.icon_key' => ['required', 'string', Rule::in(config('lead_blocks.icons'))],
            'value_points.*.line' => ['required', 'string', 'max:160'],
        ]);
    }
}
