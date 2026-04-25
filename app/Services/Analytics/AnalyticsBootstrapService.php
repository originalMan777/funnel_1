<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AnalyticsBootstrapService
{
    public function __construct(
        private readonly VisitorResolver $visitorResolver,
        private readonly SessionResolver $sessionResolver,
    ) {}

    /**
     * Build the minimal frontend analytics bootstrap contract for the current request.
     *
     * @return array<string, mixed>
     */
    public function forRequest(Request $request): array
    {
        if (! config('analytics.enabled')) {
            return [
                'enabled' => false,
                'ready' => false,
            ];
        }

        $pageKey = $this->resolvePageKey($request);

        if (! $this->isReady()) {
            return [
                'enabled' => true,
                'ready' => false,
                'page' => [
                    'key' => $pageKey,
                ],
            ];
        }

        $visitor = $this->visitorResolver->resolve($request);
        $session = $this->sessionResolver->resolve($request, $visitor, $pageKey);

        return [
            'enabled' => true,
            'ready' => true,
            'ingest_url' => route('analytics.ingest'),
            'page' => [
                'key' => $pageKey,
            ],
            'visitor' => [
                'key' => $visitor->visitor_key,
            ],
            'session' => [
                'key' => $session->session_key,
                'inactivity_timeout_minutes' => (int) config('analytics.session.inactivity_timeout_minutes', 30),
            ],
        ];
    }

    public function isReady(): bool
    {
        return Schema::hasTable('analytics_visitors')
            && Schema::hasTable('analytics_sessions');
    }

    private function resolvePageKey(Request $request): ?string
    {
        $routeName = $request->route()?->getName();
        $path = trim($request->path(), '/');

        foreach (config('public_pages.excluded_route_prefixes', []) as $prefix) {
            if ($routeName && str_starts_with($routeName, $prefix)) {
                return null;
            }
        }

        if ($routeName && in_array($routeName, config('public_pages.excluded_route_names', []), true)) {
            return null;
        }

        $routeNameKeys = config('public_pages.route_name_keys', []);

        if ($routeName && array_key_exists($routeName, $routeNameKeys)) {
            return $routeNameKeys[$routeName];
        }

        foreach (config('public_pages.route_prefix_keys', []) as $prefix => $pageKey) {
            if ($routeName && str_starts_with($routeName, $prefix)) {
                return $pageKey;
            }
        }

        $pathKeys = config('public_pages.path_keys', []);

        if (array_key_exists($path, $pathKeys)) {
            return $pathKeys[$path];
        }

        foreach (config('public_pages.path_prefix_keys', []) as $prefix => $pageKey) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $pageKey;
            }
        }

        return null;
    }
}
