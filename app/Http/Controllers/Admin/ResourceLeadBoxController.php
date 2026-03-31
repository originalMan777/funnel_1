<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ResourceLeadBoxController extends Controller
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

        return Inertia::render('Admin/LeadBoxes/Resource/Edit', [
            'mode' => 'create',
            'leadBox' => null,
            'statuses' => config('lead_blocks.statuses'),
            'visualPresets' => config('lead_blocks.resource.visual_presets'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireLeadBoxAccess($request);

        $validated = $this->validatePayload($request);

        $leadBox = LeadBox::create([
            'type' => LeadBox::TYPE_RESOURCE,
            'status' => $validated['status'],
            'internal_name' => $validated['internal_name'],
            'title' => $validated['title'],
            'short_text' => $validated['short_text'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'icon_key' => $validated['icon_key'] ?? null,
            'content' => [
                'visual_preset' => $validated['visual_preset'],
            ],
            'settings' => [],
        ]);

        return redirect()
            ->route('admin.lead-boxes.resource.edit', $leadBox)
            ->with('success', 'Resource Lead Box created.');
    }

    public function edit(Request $request, LeadBox $leadBox): Response
    {
        $this->requireLeadBoxAccess($request);

        abort_unless($leadBox->type === LeadBox::TYPE_RESOURCE, 404);

        return Inertia::render('Admin/LeadBoxes/Resource/Edit', [
            'mode' => 'edit',
            'leadBox' => [
                'id' => $leadBox->id,
                'type' => $leadBox->type,
                'status' => $leadBox->status,
                'internal_name' => $leadBox->internal_name,
                'title' => $leadBox->title,
                'short_text' => $leadBox->short_text,
                'button_text' => $leadBox->button_text,
                'icon_key' => $leadBox->icon_key,
                'visual_preset' => ($leadBox->content ?? [])['visual_preset'] ?? 'default',
            ],
            'statuses' => config('lead_blocks.statuses'),
            'visualPresets' => config('lead_blocks.resource.visual_presets'),
        ]);
    }

    public function update(Request $request, LeadBox $leadBox): RedirectResponse
    {
        $this->requireLeadBoxAccess($request);

        abort_unless($leadBox->type === LeadBox::TYPE_RESOURCE, 404);

        $validated = $this->validatePayload($request);

        $leadBox->update([
            'status' => $validated['status'],
            'internal_name' => $validated['internal_name'],
            'title' => $validated['title'],
            'short_text' => $validated['short_text'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'icon_key' => $validated['icon_key'] ?? null,
            'content' => [
                'visual_preset' => $validated['visual_preset'],
            ],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Resource Lead Box updated.');
    }

    /**
     * @return array{status:string,internal_name:string,title:string,short_text:?string,button_text:?string,icon_key:?string,visual_preset:string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'status' => ['required', Rule::in(config('lead_blocks.statuses'))],
            'internal_name' => ['required', 'string', 'max:160'],
            'title' => ['required', 'string', 'max:200'],
            'short_text' => ['nullable', 'string', 'max:1200'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'icon_key' => ['nullable', 'string', 'max:64'],
            'visual_preset' => ['required', Rule::in(config('lead_blocks.resource.visual_presets'))],
        ]);
    }
}
