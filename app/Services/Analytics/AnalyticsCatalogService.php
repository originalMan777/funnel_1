<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Cta;
use App\Models\Analytics\Page;
use App\Models\Analytics\Surface;
use Illuminate\Support\Str;

class AnalyticsCatalogService
{
    public function ensurePage(string $pageKey): Page
    {
        $registry = config("analytics.catalog.pages.{$pageKey}", []);

        return Page::query()->firstOrCreate(
            ['page_key' => $pageKey],
            [
                'label' => $registry['label'] ?? $this->headline($pageKey),
                'category' => $registry['category'] ?? 'public',
                'is_active' => true,
            ],
        );
    }

    public function ensureCta(string $ctaKey): Cta
    {
        $registry = config("analytics.catalog.ctas.{$ctaKey}", []);

        return Cta::query()->firstOrCreate(
            ['cta_key' => $ctaKey],
            [
                'label' => $registry['label'] ?? $this->headline($ctaKey),
                'cta_type_id' => $registry['cta_type_id'] ?? $this->fallbackCtaTypeId($ctaKey),
                'intent_key' => $registry['intent_key'] ?? $this->fallbackIntentKey($ctaKey),
                'is_active' => true,
            ],
        );
    }

    public function ensureSurface(string $surfaceKey): Surface
    {
        return Surface::query()->firstOrCreate(
            ['surface_key' => $surfaceKey],
            [
                'label' => $this->headline($surfaceKey),
            ],
        );
    }

    private function headline(string $value): string
    {
        return Str::headline(str_replace(['.', '_', '-'], ' ', $value));
    }

    private function fallbackCtaTypeId(string $ctaKey): int
    {
        return match (true) {
            str_starts_with($ctaKey, 'lead_box.') => (int) config('analytics.catalog.cta_types.lead_box_entry', 4),
            str_contains($ctaKey, 'consultation') => (int) config('analytics.catalog.cta_types.consultation_entry', 2),
            str_contains($ctaKey, 'articles') => (int) config('analytics.catalog.cta_types.content_entry', 3),
            default => (int) config('analytics.catalog.cta_types.primary_navigation', 1),
        };
    }

    private function fallbackIntentKey(string $ctaKey): ?string
    {
        return match (true) {
            str_contains($ctaKey, 'consultation') => 'consultation',
            str_contains($ctaKey, 'buyers') => 'buyers_strategy',
            str_contains($ctaKey, 'sellers') => 'sellers_strategy',
            str_contains($ctaKey, 'services') => 'services',
            str_starts_with($ctaKey, 'lead_box.resource') => 'resource_capture',
            str_starts_with($ctaKey, 'lead_box.service') => 'service_consultation',
            str_starts_with($ctaKey, 'lead_box.offer') => 'offer_consultation',
            default => null,
        };
    }
}
