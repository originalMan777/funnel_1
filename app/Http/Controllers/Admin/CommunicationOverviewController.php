<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\MarketingContactSync;
use App\Services\Communications\CommunicationRuntimeConfig;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationOverviewController extends Controller
{
    private const RECENT_WINDOW_DAYS = 7;

    public function __construct(
        private readonly CommunicationRuntimeConfig $runtimeConfig,
    ) {}

    public function index(): Response
    {
        $recentWindowStart = now()->subDays(self::RECENT_WINDOW_DAYS);

        return Inertia::render('Admin/Communications/Overview', [
            'summary' => [
                'events_total' => CommunicationEvent::query()->count(),
                'events_pending' => CommunicationEvent::query()->where('status', CommunicationEvent::STATUS_PENDING)->count(),
                'events_processing' => CommunicationEvent::query()->where('status', CommunicationEvent::STATUS_PROCESSING)->count(),
                'events_processed' => CommunicationEvent::query()->where('status', CommunicationEvent::STATUS_PROCESSED)->count(),
                'events_partial_failure' => CommunicationEvent::query()->where('status', CommunicationEvent::STATUS_PARTIAL_FAILURE)->count(),
                'events_failed' => CommunicationEvent::query()->where('status', CommunicationEvent::STATUS_FAILED)->count(),
                'events_skipped' => CommunicationEvent::query()->where('status', CommunicationEvent::STATUS_SKIPPED)->count(),
                'recent_deliveries_sent' => CommunicationDelivery::query()
                    ->where('status', CommunicationDelivery::STATUS_SENT)
                    ->where('sent_at', '>=', $recentWindowStart)
                    ->count(),
                'recent_deliveries_failed' => CommunicationDelivery::query()
                    ->where('status', CommunicationDelivery::STATUS_FAILED)
                    ->where('created_at', '>=', $recentWindowStart)
                    ->count(),
                'marketing_syncs_failed' => MarketingContactSync::query()
                    ->where('last_sync_status', MarketingContactSync::STATUS_FAILED)
                    ->count(),
                'recent_window_days' => self::RECENT_WINDOW_DAYS,
            ],
            'providers' => [
                'transactional' => $this->runtimeConfig->transactionalProvider(),
                'marketing' => $this->runtimeConfig->marketingProvider(),
            ],
            'recentEvents' => CommunicationEvent::query()
                ->latest('created_at')
                ->limit(8)
                ->get(['id', 'event_key', 'status', 'created_at', 'processed_at'])
                ->map(fn (CommunicationEvent $event) => [
                    'id' => $event->id,
                    'event_key' => $event->event_key,
                    'status' => $event->status,
                    'created_at' => optional($event->created_at)?->toISOString(),
                    'processed_at' => optional($event->processed_at)?->toISOString(),
                ]),
        ]);
    }
}
