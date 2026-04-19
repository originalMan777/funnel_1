<?php

namespace App\Services\Leads;

use App\Models\Acquisition;
use App\Models\AcquisitionPath;
use App\Models\LeadAssignment;
use App\Models\Service;
use Illuminate\Http\Request;

class LeadAcquisitionResolver
{
    /**
     * @return array{
     *   acquisitionId:int|null,
     *   serviceId:int|null,
     *   acquisitionPathId:int|null,
     *   acquisitionPathKey:string|null,
     *   sourcePageKey:string|null,
     *   sourceSlotKey:string|null,
     *   sourcePopupKey:string|null
     * }
     */
    public function resolveForLeadRequest(
        Request $request,
        ?LeadAssignment $assignment = null,
        ?string $fallbackPageKey = null
    ): array {
        $sourcePageKey = $this->cleanString($request->input('page_key')) ?? $fallbackPageKey;
        $sourceSlotKey = $this->cleanString($request->input('lead_slot_key'));
        $sourcePopupKey = $this->cleanString($request->input('source_popup_key'));

        $resolved = $this->resolveExplicitContext(
            acquisitionPathKey: $this->cleanString($request->input('acquisition_path_key')),
            acquisitionSlug: $this->cleanString($request->input('acquisition_slug')),
            serviceSlug: $this->cleanString($request->input('service_slug')),
        );

        if ($resolved === null) {
            $resolved = $this->resolveAssignmentContext($assignment);
        }

        if ($resolved === null) {
            $resolved = $this->resolvePageFallback($sourcePageKey);
        }

        if ($resolved === null) {
            $resolved = $this->resolveFinalFallback();
        }

        return [
            'acquisitionId' => $resolved['acquisition_id'] ?? null,
            'serviceId' => $resolved['service_id'] ?? null,
            'acquisitionPathId' => $resolved['acquisition_path_id'] ?? null,
            'acquisitionPathKey' => $resolved['acquisition_path_key'] ?? null,
            'sourcePageKey' => $sourcePageKey,
            'sourceSlotKey' => $sourceSlotKey,
            'sourcePopupKey' => $sourcePopupKey,
        ];
    }

    /**
     * @return array{
     *   acquisitionId:int|null,
     *   serviceId:int|null,
     *   acquisitionPathId:int|null,
     *   acquisitionPathKey:string|null,
     *   sourcePageKey:string|null,
     *   sourceSlotKey:string|null,
     *   sourcePopupKey:string|null
     * }
     */
    public function resolveForPopupRequest(
        Request $request,
        ?string $popupSlug = null,
        ?string $popupLeadType = null
    ): array {
        $sourcePageKey = $this->cleanString($request->input('page_key'));
        $sourcePopupKey = $this->cleanString($request->input('source_popup_key')) ?? $popupSlug;

        $resolved = $this->resolveExplicitContext(
            acquisitionPathKey: $this->cleanString($request->input('acquisition_path_key')),
            acquisitionSlug: $this->cleanString($request->input('acquisition_slug')),
            serviceSlug: $this->cleanString($request->input('service_slug')),
        );

        if ($resolved === null) {
            $resolved = $this->resolvePageFallback($sourcePageKey);
        }

        if ($resolved === null && $popupLeadType !== null) {
            $resolved = $this->resolvePopupLeadTypeFallback($popupLeadType);
        }

        if ($resolved === null) {
            $resolved = $this->resolveFinalFallback();
        }

        return [
            'acquisitionId' => $resolved['acquisition_id'] ?? null,
            'serviceId' => $resolved['service_id'] ?? null,
            'acquisitionPathId' => $resolved['acquisition_path_id'] ?? null,
            'acquisitionPathKey' => $resolved['acquisition_path_key'] ?? null,
            'sourcePageKey' => $sourcePageKey,
            'sourceSlotKey' => null,
            'sourcePopupKey' => $sourcePopupKey,
        ];
    }

    /**
     * @return array{
     *   acquisition_id:int|null,
     *   service_id:int|null,
     *   acquisition_path_id:int|null,
     *   acquisition_path_key:string|null
     * }|null
     */
    private function resolveExplicitContext(
        ?string $acquisitionPathKey,
        ?string $acquisitionSlug,
        ?string $serviceSlug
    ): ?array {
        if ($acquisitionPathKey !== null) {
            $path = AcquisitionPath::query()
                ->where('path_key', $acquisitionPathKey)
                ->where('is_active', true)
                ->first();

            if ($path !== null) {
                return [
                    'acquisition_id' => $path->acquisition_id,
                    'service_id' => $path->service_id,
                    'acquisition_path_id' => $path->id,
                    'acquisition_path_key' => $path->path_key,
                ];
            }
        }

        $acquisition = null;
        if ($acquisitionSlug !== null) {
            $acquisition = Acquisition::query()
                ->where('slug', $acquisitionSlug)
                ->where('is_active', true)
                ->first();
        }

        $service = null;
        if ($serviceSlug !== null) {
            $serviceQuery = Service::query()
                ->where('slug', $serviceSlug)
                ->where('is_active', true);

            if ($acquisition !== null) {
                $service = $serviceQuery
                    ->where('acquisition_id', $acquisition->id)
                    ->first();
            } else {
                $services = $serviceQuery->get();
                if ($services->count() === 1) {
                    $service = $services->first();
                    $acquisition = $acquisition ?? Acquisition::query()->find($service->acquisition_id);
                }
            }
        }

        if ($acquisition === null && $service === null) {
            return null;
        }

        return [
            'acquisition_id' => $acquisition?->id ?? $service?->acquisition_id,
            'service_id' => $service?->id,
            'acquisition_path_id' => null,
            'acquisition_path_key' => null,
        ];
    }

    /**
     * @return array{
     *   acquisition_id:int|null,
     *   service_id:int|null,
     *   acquisition_path_id:int|null,
     *   acquisition_path_key:string|null
     * }|null
     */
    private function resolveAssignmentContext(?LeadAssignment $assignment): ?array
    {
        if ($assignment === null) {
            return null;
        }

        if (
            $assignment->acquisition_id === null
            && $assignment->service_id === null
            && $assignment->acquisition_path_id === null
            && $assignment->acquisition_path_key === null
        ) {
            return null;
        }

        return [
            'acquisition_id' => $assignment->acquisition_id,
            'service_id' => $assignment->service_id,
            'acquisition_path_id' => $assignment->acquisition_path_id,
            'acquisition_path_key' => $assignment->acquisition_path_key,
        ];
    }

    /**
     * @return array{
     *   acquisition_id:int|null,
     *   service_id:int|null,
     *   acquisition_path_id:int|null,
     *   acquisition_path_key:string|null
     * }|null
     */
    private function resolvePageFallback(?string $pageKey): ?array
    {
        if ($pageKey === null) {
            return null;
        }

        $fallback = config("lead_acquisition.page_fallbacks.{$pageKey}");
        if (! is_array($fallback)) {
            return null;
        }

        return $this->resolveFallbackDefinition($fallback);
    }

    /**
     * @return array{
     *   acquisition_id:int|null,
     *   service_id:int|null,
     *   acquisition_path_id:int|null,
     *   acquisition_path_key:string|null
     * }|null
     */
    private function resolvePopupLeadTypeFallback(string $popupLeadType): ?array
    {
        $fallback = config("lead_acquisition.popup_lead_type_fallbacks.{$popupLeadType}");
        if (! is_array($fallback)) {
            return null;
        }

        return $this->resolveFallbackDefinition($fallback);
    }

    /**
     * @return array{
     *   acquisition_id:int|null,
     *   service_id:int|null,
     *   acquisition_path_id:int|null,
     *   acquisition_path_key:string|null
     * }|null
     */
    private function resolveFinalFallback(): ?array
    {
        $fallback = config('lead_acquisition.default_fallback');

        return is_array($fallback) ? $this->resolveFallbackDefinition($fallback) : null;
    }

    /**
     * @param array<string, mixed> $fallback
     * @return array{
     *   acquisition_id:int|null,
     *   service_id:int|null,
     *   acquisition_path_id:int|null,
     *   acquisition_path_key:string|null
     * }|null
     */
    private function resolveFallbackDefinition(array $fallback): ?array
    {
        return $this->resolveExplicitContext(
            acquisitionPathKey: $this->cleanString($fallback['acquisition_path_key'] ?? null),
            acquisitionSlug: $this->cleanString($fallback['acquisition_slug'] ?? null),
            serviceSlug: $this->cleanString($fallback['service_slug'] ?? null),
        );
    }

    private function cleanString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
