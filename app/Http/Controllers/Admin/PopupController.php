<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Popup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PopupController extends Controller
{
    private function requirePopupAccess(Request $request): void
    {
        if (! $request->user()?->canManagePopups()) {
            abort(403);
        }
    }

    public function index(Request $request): Response
    {
        $this->requirePopupAccess($request);

        $popups = Popup::query()
            ->orderBy('priority')
            ->latest('updated_at')
            ->get()
            ->map(fn (Popup $popup) => [
                'id' => $popup->id,
                'name' => $popup->name,
                'slug' => $popup->slug,
                'type' => $popup->type,
                'role' => $popup->role,
                'priority' => $popup->priority,
                'audience' => $popup->audience,
                'is_active' => $popup->is_active,
                'headline' => $popup->headline,
                'layout' => $popup->layout,
                'trigger_type' => $popup->trigger_type,
                'target_pages' => $popup->target_pages ?? [],
                'suppress_if_lead_captured' => $popup->suppress_if_lead_captured,
                'suppression_scope' => $popup->suppression_scope,
                'post_submit_action' => $popup->post_submit_action,
                'updated_at' => optional($popup->updated_at)?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/popups/Index', [
            'popups' => $popups,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->requirePopupAccess($request);

        return Inertia::render('Admin/popups/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePopupAccess($request);

        $validated = $this->preparePayload($request, $this->validatePopup($request));

        Popup::create($validated);

        return redirect()
            ->route('admin.popups.index')
            ->with('success', 'Popup created successfully.');
    }

    public function edit(Request $request, Popup $popup): Response
    {
        $this->requirePopupAccess($request);

        return Inertia::render('Admin/popups/Edit', [
            'popup' => [
                'id' => $popup->id,
                'name' => $popup->name,
                'slug' => $popup->slug,
                'type' => $popup->type,
                'role' => $popup->role,
                'priority' => $popup->priority,
                'is_active' => $popup->is_active,
                'eyebrow' => $popup->eyebrow,
                'headline' => $popup->headline,
                'body' => $popup->body,
                'cta_text' => $popup->cta_text,
                'success_message' => $popup->success_message,
                'layout' => $popup->layout,
                'trigger_type' => $popup->trigger_type,
                'trigger_delay' => $popup->trigger_delay,
                'trigger_scroll' => $popup->trigger_scroll,
                'target_pages' => $popup->target_pages ?? [],
                'device' => $popup->device,
                'frequency' => $popup->frequency,
                'audience' => $popup->audience,
                'suppress_if_lead_captured' => $popup->suppress_if_lead_captured,
                'suppression_scope' => $popup->suppression_scope,
                'form_fields' => $popup->form_fields ?? [],
                'lead_type' => $popup->lead_type,
                'post_submit_action' => $popup->post_submit_action,
                'post_submit_redirect_url' => $popup->post_submit_redirect_url,
            ],
        ]);
    }

    public function update(Request $request, Popup $popup): RedirectResponse
    {
        $this->requirePopupAccess($request);

        $validated = $this->preparePayload($request, $this->validatePopup($request, $popup->id));

        $popup->update($validated);

        return redirect()
            ->route('admin.popups.index')
            ->with('success', 'Popup updated successfully.');
    }

    public function destroy(Request $request, Popup $popup): RedirectResponse
    {
        $this->requirePopupAccess($request);

        $popup->delete();

        return redirect()
            ->route('admin.popups.index')
            ->with('success', 'Popup deleted successfully.');
    }

    private function validatePopup(Request $request, ?int $popupId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:popups,slug,'.($popupId ?? 'NULL').',id'],
            'type' => ['required', 'in:general,buyer,seller,consultation,resource'],
            'role' => ['required', 'in:primary,fallback,standard'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],

            'eyebrow' => ['nullable', 'string', 'max:255'],
            'headline' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'cta_text' => ['required', 'string', 'max:255'],
            'success_message' => ['nullable', 'string'],

            'layout' => ['required', 'in:centered,split,banner'],

            'trigger_type' => ['required', 'in:time,scroll,exit,click'],
            'trigger_delay' => ['nullable', 'integer', 'min:0', 'max:999'],
            'trigger_scroll' => ['nullable', 'integer', 'min:0', 'max:100'],

            'target_pages' => ['nullable', 'array'],
            'target_pages.*' => ['string', 'in:home,about,services,buyers,sellers,consultation,resources,contact,blog'],

            'device' => ['required', 'in:all,desktop,mobile'],
            'frequency' => ['required', 'in:once_session,once_day,always'],
            'audience' => ['required', 'in:everyone,guests,authenticated'],
            'suppress_if_lead_captured' => ['nullable', 'boolean'],
            'suppression_scope' => ['required', 'in:this_popup_only,all_lead_popups'],

            'form_fields' => ['nullable', 'array'],
            'form_fields.*' => ['string', 'in:name,email,phone,message'],

            'lead_type' => ['required', 'in:general,buyer,seller'],
            'post_submit_action' => ['required', 'in:message,redirect'],
            'post_submit_redirect_url' => ['nullable', 'string', 'max:2048'],
        ]);
    }

    private function preparePayload(Request $request, array $validated): array
    {
        $validated['slug'] = Str::slug(($validated['slug'] ?? '') !== '' ? $validated['slug'] : $validated['name']);
        $validated['target_pages'] = array_values($validated['target_pages'] ?? []);
        $validated['form_fields'] = array_values($validated['form_fields'] ?? []);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['suppress_if_lead_captured'] = $request->boolean('suppress_if_lead_captured');
        $validated['priority'] = (int) ($validated['priority'] ?? 100);

        if (($validated['post_submit_action'] ?? 'message') !== 'redirect') {
            $validated['post_submit_redirect_url'] = null;
        }

        return $validated;
    }
}
