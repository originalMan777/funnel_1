<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadBox;
use App\Services\LeadSlots\DuplicateLeadBoxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadBoxDuplicateController extends Controller
{
    public function __invoke(Request $request, LeadBox $leadBox, DuplicateLeadBoxService $duplicator): RedirectResponse
    {
        if (! $request->user()?->canManageLeadBoxes()) {
            abort(403);
        }

        $duplicate = $duplicator->duplicate($leadBox);

        return redirect()
            ->route($this->editRouteName($duplicate), $duplicate)
            ->with('success', 'Lead Box duplicated as draft.');
    }

    private function editRouteName(LeadBox $leadBox): string
    {
        return match ($leadBox->type) {
            LeadBox::TYPE_RESOURCE => 'admin.lead-boxes.resource.edit',
            LeadBox::TYPE_SERVICE => 'admin.lead-boxes.service.edit',
            LeadBox::TYPE_OFFER => 'admin.lead-boxes.offer.edit',
            default => 'admin.lead-boxes.edit',
        };
    }
}
