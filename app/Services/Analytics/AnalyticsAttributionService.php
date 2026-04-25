<?php

namespace App\Services\Analytics;

use App\Models\Analytics\AttributionTouch;
use App\Models\Analytics\Conversion;
use App\Models\Analytics\ConversionAttribution;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AnalyticsAttributionService
{
    public const SCOPE_FIRST_TOUCH = 'first_touch';

    public const SCOPE_LAST_TOUCH = 'last_touch';

    public const SCOPE_CONVERSION_TOUCH = 'conversion_touch';

    /**
     * @return Collection<int, ConversionAttribution>
     */
    public function syncRange(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Conversion::query()
            ->whereBetween('occurred_at', [
                $from->copy()->startOfDay()->toDateTimeString(),
                $to->copy()->endOfDay()->toDateTimeString(),
            ])
            ->with([
                'session.entryPage',
                'session.attributionTouches.landingPage',
                'session.events.eventType',
                'session.events.cta',
                'session.events.leadBox',
                'session.events.popup',
            ])
            ->orderBy('occurred_at')
            ->get()
            ->flatMap(fn (Conversion $conversion) => $this->syncConversion($conversion));
    }

    /**
     * @return Collection<int, ConversionAttribution>
     */
    public function syncConversion(Conversion $conversion): Collection
    {
        $conversion->loadMissing([
            'session.entryPage',
            'session.attributionTouches.landingPage',
            'session.events.eventType',
            'session.events.cta',
            'session.events.leadBox',
            'session.events.popup',
        ]);

        $snapshots = collect([
            $this->buildTouchSnapshot($conversion, self::SCOPE_FIRST_TOUCH),
            $this->buildTouchSnapshot($conversion, self::SCOPE_LAST_TOUCH),
            $this->buildConversionTouchSnapshot($conversion),
        ])->filter();

        return $snapshots
            ->map(function (array $snapshot) use ($conversion): ConversionAttribution {
                return ConversionAttribution::query()->updateOrCreate(
                    [
                        'conversion_id' => $conversion->id,
                        'attribution_scope' => $snapshot['attribution_scope'],
                    ],
                    $snapshot,
                );
            })
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildTouchSnapshot(Conversion $conversion, string $scope): ?array
    {
        $session = $conversion->session;
        $touches = $session?->attributionTouches
            ?->filter(fn (AttributionTouch $touch) => ! $touch->occurred_at || ! $conversion->occurred_at || $touch->occurred_at->lte($conversion->occurred_at))
            ->sortBy(fn (AttributionTouch $touch) => sprintf('%s-%010d', optional($touch->occurred_at)?->format('YmdHis'), $touch->id))
            ->values() ?? collect();

        $touch = $scope === self::SCOPE_FIRST_TOUCH
            ? $touches->first()
            : $touches->last();

        if ($touch) {
            return [
                'session_id' => $conversion->session_id,
                'visitor_id' => $conversion->visitor_id,
                'attribution_touch_id' => $touch->id,
                'landing_page_id' => $touch->landing_page_id,
                'source_key' => $this->observedSourceKey($touch),
                'source_label' => $this->observedSourceLabel($touch),
                'referrer_host' => $touch->referrer_host,
                'utm_source' => $touch->utm_source,
                'utm_medium' => $touch->utm_medium,
                'utm_campaign' => $touch->utm_campaign,
                'attribution_scope' => $scope,
                'attribution_method' => (string) ($touch->attribution_method ?: 'observed_touch'),
                'attribution_confidence' => (float) ($touch->attribution_confidence ?? 1),
                'occurred_at' => $touch->occurred_at ?? $conversion->occurred_at ?? now(),
                'properties' => [
                    'source_origin' => 'observed_touch',
                ],
            ];
        }

        if (! $session) {
            return null;
        }

        $sourceKey = $this->fallbackSessionSourceKey($session);
        $sourceLabel = $this->fallbackSessionSourceLabel($session);

        if ($sourceKey === null) {
            return null;
        }

        return [
            'session_id' => $conversion->session_id,
            'visitor_id' => $conversion->visitor_id,
            'attribution_touch_id' => null,
            'landing_page_id' => $session->entry_page_id,
            'source_key' => $sourceKey,
            'source_label' => $sourceLabel,
            'referrer_host' => $session->referrer_host,
            'utm_source' => $session->utm_source,
            'utm_medium' => $session->utm_medium,
            'utm_campaign' => $session->utm_campaign,
            'attribution_scope' => $scope,
            'attribution_method' => 'session_entry_fallback',
            'attribution_confidence' => 0.6,
            'occurred_at' => $conversion->occurred_at ?? $session->started_at ?? now(),
            'properties' => [
                'source_origin' => 'session_fallback',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildConversionTouchSnapshot(Conversion $conversion): ?array
    {
        $session = $conversion->session;

        if (! $session) {
            return null;
        }

        $events = $session->events
            ->filter(fn ($event) => ! $event->occurred_at || ! $conversion->occurred_at || $event->occurred_at->lte($conversion->occurred_at))
            ->sortByDesc(fn ($event) => sprintf('%s-%010d', optional($event->occurred_at)?->format('YmdHis'), $event->id))
            ->values();
        $conversionTouch = $events->first(function ($event): bool {
            return in_array($event->eventType?->event_key, config('analytics.attribution.conversion_touch_event_keys', []), true);
        });

        if ($conversionTouch) {
            [$sourceKey, $sourceLabel] = $this->conversionTouchSource($conversionTouch);

            return [
                'session_id' => $conversion->session_id,
                'visitor_id' => $conversion->visitor_id,
                'attribution_touch_id' => null,
                'landing_page_id' => $conversionTouch->page_id ?? $session->entry_page_id,
                'source_key' => $sourceKey,
                'source_label' => $sourceLabel,
                'referrer_host' => $session->referrer_host,
                'utm_source' => null,
                'utm_medium' => null,
                'utm_campaign' => null,
                'attribution_scope' => self::SCOPE_CONVERSION_TOUCH,
                'attribution_method' => 'conversion_touch_event',
                'attribution_confidence' => 0.85,
                'occurred_at' => $conversionTouch->occurred_at ?? $conversion->occurred_at ?? now(),
                'properties' => [
                    'event_key' => $conversionTouch->eventType?->event_key,
                ],
            ];
        }

        $fallback = $this->buildTouchSnapshot($conversion, self::SCOPE_LAST_TOUCH);

        if (! $fallback) {
            return null;
        }

        $fallback['attribution_scope'] = self::SCOPE_CONVERSION_TOUCH;
        $fallback['attribution_method'] = 'conversion_touch_fallback';
        $fallback['attribution_confidence'] = 0.5;
        $fallback['properties'] = [
            ...($fallback['properties'] ?? []),
            'source_origin' => 'conversion_touch_fallback',
        ];

        return $fallback;
    }

    private function observedSourceKey(AttributionTouch $touch): ?string
    {
        if ($touch->utm_source || $touch->utm_medium || $touch->utm_campaign) {
            return collect([$touch->utm_source, $touch->utm_medium, $touch->utm_campaign])
                ->filter(fn (?string $value) => filled($value))
                ->implode('|');
        }

        if ($touch->referrer_host) {
            return 'referrer:'.$touch->referrer_host;
        }

        return null;
    }

    private function observedSourceLabel(AttributionTouch $touch): ?string
    {
        if ($touch->utm_source || $touch->utm_medium) {
            return trim(collect([$touch->utm_source, $touch->utm_medium])
                ->filter(fn (?string $value) => filled($value))
                ->implode(' / '));
        }

        return $touch->referrer_host;
    }

    private function fallbackSessionSourceKey($session): ?string
    {
        if ($session->utm_source || $session->utm_medium || $session->utm_campaign) {
            return collect([$session->utm_source, $session->utm_medium, $session->utm_campaign])
                ->filter(fn (?string $value) => filled($value))
                ->implode('|');
        }

        if ($session->referrer_host) {
            return 'referrer:'.$session->referrer_host;
        }

        return config('analytics.attribution.fallback_direct_source', 'direct').'|'.config('analytics.attribution.fallback_direct_medium', 'none');
    }

    private function fallbackSessionSourceLabel($session): string
    {
        if ($session->utm_source || $session->utm_medium) {
            return trim(collect([$session->utm_source, $session->utm_medium])
                ->filter(fn (?string $value) => filled($value))
                ->implode(' / '));
        }

        if ($session->referrer_host) {
            return $session->referrer_host;
        }

        return 'Direct / None';
    }

    /**
     * @return array{0:string|null,1:string|null}
     */
    private function conversionTouchSource($event): array
    {
        return match ($event->eventType?->event_key) {
            'popup.submitted' => [
                $event->popup_id ? "popup:{$event->popup_id}" : 'popup',
                $event->popup?->name ?? 'Popup submit',
            ],
            'lead_form.submitted' => [
                $event->lead_box_id ? "lead_box:{$event->lead_box_id}" : 'lead_form',
                $event->leadBox?->title ?? 'Lead form submit',
            ],
            'cta.click' => [
                $event->cta_id ? "cta:{$event->cta_id}" : 'cta',
                $event->cta?->label ?? 'CTA click',
            ],
            default => [null, null],
        };
    }
}
