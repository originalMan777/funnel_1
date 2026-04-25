<?php

namespace App\Services\Analytics;

use App\Models\Lead;
use App\Models\Popup;
use App\Models\PopupLead;
use Illuminate\Http\Request;

class AnalyticsObservationService
{
    public function __construct(
        private readonly VisitorResolver $visitorResolver,
        private readonly SessionResolver $sessionResolver,
        private readonly EventRecorder $eventRecorder,
        private readonly ConversionProjector $conversionProjector,
        private readonly AnalyticsBootstrapService $analyticsBootstrapService,
    ) {}

    public function recordLeadSubmission(Request $request, Lead $lead): void
    {
        if (! $this->analyticsBootstrapService->isReady()) {
            return;
        }

        $context = $this->requestContext($request);

        $this->eventRecorder->record([
            ...$context,
            'event_key' => 'lead_form.submitted',
            'page_key' => $lead->page_key,
            'lead_box_id' => $lead->lead_box_id,
            'occurred_at' => $lead->created_at ?? now(),
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'properties' => [
                'status' => 'submitted',
                'source' => 'lead_controller',
            ],
        ]);

        $this->conversionProjector->project([
            ...$context,
            'conversion_type_id' => (int) config('analytics.catalog.conversion_types.lead_form_submission', 1),
            'source_type' => Lead::class,
            'source_id' => $lead->id,
            'lead_id' => $lead->id,
            'page_key' => $lead->page_key,
            'lead_box_id' => $lead->lead_box_id,
            'occurred_at' => $lead->created_at ?? now(),
            'properties' => [
                'source' => 'lead_box',
                'status' => 'submitted',
            ],
        ]);

        $this->eventRecorder->record([
            ...$context,
            'event_key' => 'conversion.recorded',
            'page_key' => $lead->page_key,
            'lead_box_id' => $lead->lead_box_id,
            'occurred_at' => $lead->created_at ?? now(),
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'properties' => [
                'source' => 'lead_box',
                'status' => 'recorded',
            ],
        ]);
    }

    public function recordPopupSubmission(Request $request, PopupLead $popupLead, Popup $popup): void
    {
        if (! $this->analyticsBootstrapService->isReady()) {
            return;
        }

        $context = $this->requestContext($request);

        $this->eventRecorder->record([
            ...$context,
            'event_key' => 'popup.submitted',
            'page_key' => $popupLead->page_key,
            'popup_id' => $popup->id,
            'occurred_at' => $popupLead->created_at ?? now(),
            'subject_type' => PopupLead::class,
            'subject_id' => $popupLead->id,
            'properties' => [
                'status' => 'submitted',
                'source' => 'popup_controller',
            ],
        ]);

        $this->conversionProjector->project([
            ...$context,
            'conversion_type_id' => (int) config('analytics.catalog.conversion_types.popup_submission', 2),
            'source_type' => PopupLead::class,
            'source_id' => $popupLead->id,
            'popup_lead_id' => $popupLead->id,
            'page_key' => $popupLead->page_key,
            'popup_id' => $popup->id,
            'occurred_at' => $popupLead->created_at ?? now(),
            'properties' => [
                'source' => 'popup',
                'status' => 'submitted',
            ],
        ]);

        $this->eventRecorder->record([
            ...$context,
            'event_key' => 'conversion.recorded',
            'page_key' => $popupLead->page_key,
            'popup_id' => $popup->id,
            'occurred_at' => $popupLead->created_at ?? now(),
            'subject_type' => PopupLead::class,
            'subject_id' => $popupLead->id,
            'properties' => [
                'source' => 'popup',
                'status' => 'recorded',
            ],
        ]);
    }

    /**
     * @return array{visitor_key:?string,session_key:?string}
     */
    private function requestContext(Request $request): array
    {
        return [
            'visitor_key' => $this->visitorResolver->extractVisitorKey($request),
            'session_key' => $this->sessionResolver->extractSessionKey($request),
        ];
    }
}
