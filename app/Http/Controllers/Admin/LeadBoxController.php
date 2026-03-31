<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadBox;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadBoxController extends Controller
{
    private function requireLeadBoxAccess(Request $request): void
    {
        if (! $request->user()?->canManageLeadBoxes()) {
            abort(403);
        }
    }

    public function index(Request $request): Response
    {
        $this->requireLeadBoxAccess($request);

        $leadBoxes = LeadBox::query()
            ->latest('updated_at')
            ->get()
            ->map(fn (LeadBox $box) => [
                'id' => $box->id,
                'type' => $box->type,
                'status' => $box->status,
                'internal_name' => $box->internal_name,
                'title' => $box->title,
                'updated_at' => optional($box->updated_at)?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/LeadBoxes/Index', [
            'leadBoxes' => $leadBoxes,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->requireLeadBoxAccess($request);

        return Inertia::render('Admin/LeadBoxes/Create', [
            'statuses' => config('lead_blocks.statuses'),
            'icons' => config('lead_blocks.icons'),
            'visualPresets' => config('lead_blocks.resource.visual_presets'),
        ]);
    }

    public function edit(Request $request, LeadBox $leadBox): Response
    {
        $this->requireLeadBoxAccess($request);

        return Inertia::render('Admin/LeadBoxes/Edit', [
            'leadBox' => [
                'id' => $leadBox->id,
                'type' => $leadBox->type,
                'status' => $leadBox->status,
                'internal_name' => $leadBox->internal_name,
                'title' => $leadBox->title,
                'short_text' => $leadBox->short_text,
                'button_text' => $leadBox->button_text,
                'icon_key' => $leadBox->icon_key,
                'content' => $leadBox->content ?? [],
            ],
            'statuses' => config('lead_blocks.statuses'),
            'icons' => config('lead_blocks.icons'),
            'visualPresets' => config('lead_blocks.resource.visual_presets'),
        ]);
    }
}
