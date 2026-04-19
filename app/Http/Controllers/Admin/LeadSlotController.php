<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Acquisition;
use App\Models\AcquisitionPath;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeadSlotController extends Controller
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

        $slots = collect($this->slotDefinitions())
            ->map(function (array $definition, string $slotKey) {
                $slot = LeadSlot::query()->firstOrCreate(
                    ['key' => $slotKey],
                    ['is_enabled' => true],
                );

                return [
                    'id' => $slot->id,
                    'key' => $slot->key,
                    'label' => $definition['label'],
                    'is_enabled' => $slot->is_enabled,
                    'required_type' => $definition['required_type'],
                    'assignment_lead_box_id' => optional($slot->assignment)?->lead_box_id,
                    'assignment_acquisition_id' => optional($slot->assignment)?->acquisition_id,
                    'assignment_service_id' => optional($slot->assignment)?->service_id,
                    'assignment_acquisition_path_id' => optional($slot->assignment)?->acquisition_path_id,
                    'assignment_acquisition_path_key' => optional($slot->assignment)?->acquisition_path_key,
                ];
            })
            ->values();

        $activeLeadBoxes = LeadBox::query()
            ->where('status', LeadBox::STATUS_ACTIVE)
            ->orderBy('internal_name')
            ->get()
            ->map(fn (LeadBox $box) => [
                'id' => $box->id,
                'internal_name' => $box->internal_name,
                'title' => $box->title,
                'type' => $box->type,
            ])
            ->values();

        $acquisitions = Acquisition::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Acquisition $acquisition) => [
                'id' => $acquisition->id,
                'name' => $acquisition->name,
                'slug' => $acquisition->slug,
            ])
            ->values();

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
                'acquisition_id' => $service->acquisition_id,
            ])
            ->values();

        $acquisitionPaths = AcquisitionPath::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (AcquisitionPath $path) => [
                'id' => $path->id,
                'name' => $path->name,
                'path_key' => $path->path_key,
                'acquisition_id' => $path->acquisition_id,
                'service_id' => $path->service_id,
            ])
            ->values();

        return Inertia::render('Admin/LeadSlots/Index', [
            'slots' => $slots,
            'activeLeadBoxes' => $activeLeadBoxes,
            'acquisitions' => $acquisitions,
            'services' => $services,
            'acquisitionPaths' => $acquisitionPaths,
        ]);
    }

    public function update(Request $request, LeadSlot $leadSlot): RedirectResponse
    {
        $this->requireLeadBoxAccess($request);

        abort_unless(array_key_exists($leadSlot->key, $this->slotDefinitions()), 404);

        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'lead_box_id' => ['nullable', 'integer', Rule::exists('lead_boxes', 'id')],
            'acquisition_id' => ['nullable', 'integer', Rule::exists('acquisitions', 'id')],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'acquisition_path_id' => ['nullable', 'integer', Rule::exists('acquisition_paths', 'id')],
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

        if ($leadBox->status !== LeadBox::STATUS_ACTIVE) {
            return redirect()
                ->back()
                ->withErrors([
                    'lead_box_id' => 'Only Active Lead Boxes can be assigned to this slot.',
                ]);
        }

        $acquisitionId = $validated['acquisition_id'] ?? null;
        $serviceId = $validated['service_id'] ?? null;
        $acquisitionPathId = $validated['acquisition_path_id'] ?? null;

        $service = $serviceId !== null ? Service::query()->findOrFail((int) $serviceId) : null;
        $path = $acquisitionPathId !== null ? AcquisitionPath::query()->findOrFail((int) $acquisitionPathId) : null;

        if ($acquisitionId === null) {
            $serviceId = null;
            $service = null;
            $acquisitionPathId = null;
            $path = null;
        }

        if ($service !== null && $service->acquisition_id !== (int) $acquisitionId) {
            $serviceId = null;
            $service = null;
        }

        if ($path !== null && $path->acquisition_id !== (int) $acquisitionId) {
            $acquisitionPathId = null;
            $path = null;
        }

        if ($path !== null && $path->service_id !== null && ($service === null || $path->service_id !== $service->id)) {
            $acquisitionPathId = null;
            $path = null;
        }

        LeadAssignment::query()->updateOrCreate(
            ['lead_slot_id' => $leadSlot->id],
            [
                'lead_box_id' => $leadBox->id,
                'acquisition_id' => $acquisitionId,
                'service_id' => $serviceId,
                'acquisition_path_id' => $acquisitionPathId,
                'acquisition_path_key' => $path?->path_key,
            ],
        );

        return redirect()
            ->back()
            ->with('success', 'Slot updated.');
    }

    /**
     * @return array<string, array{label:string,required_type:string,page_key:string}>
     */
    private function slotDefinitions(): array
    {
        return config('lead_blocks.slot_definitions', []);
    }
}
