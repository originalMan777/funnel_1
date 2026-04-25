<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class SessionResolver
{
    public function __construct(
        private readonly AnalyticsCatalogService $catalogService,
    ) {}

    /**
     * Resolve or create the current analytics session without taking over auth/session ownership.
     */
    public function resolve(Request $request, ?Visitor $visitor = null, ?string $pageKey = null): Session
    {
        $now = now();
        $session = $this->findExistingSession($request);

        if ($session && $this->isExpired($session, $now)) {
            $session->forceFill([
                'ended_at' => $session->updated_at ?? $now,
            ])->save();
            $session = null;
        }

        if ($session) {
            if ($visitor && $session->visitor_id !== $visitor->id) {
                $session->visitor()->associate($visitor);
            }

            $session->ended_at = null;
            $session->save();
            $session->touch();
            $this->queueCookie($session->session_key);

            return $session->fresh();
        }

        $session = Session::query()->create([
            'session_key' => (string) Str::uuid(),
            'visitor_id' => $visitor?->id,
            'started_at' => $now,
            'entry_page_id' => $this->resolvePageId($pageKey),
            'entry_url' => $request->fullUrl(),
            'entry_path' => '/'.ltrim($request->path(), '/'),
            'referrer_url' => $this->normalizeUrl($request->headers->get('referer')),
            'referrer_host' => parse_url((string) $request->headers->get('referer'), PHP_URL_HOST) ?: null,
            'utm_source' => $this->queryValue($request, 'utm_source'),
            'utm_medium' => $this->queryValue($request, 'utm_medium'),
            'utm_campaign' => $this->queryValue($request, 'utm_campaign'),
            'utm_term' => $this->queryValue($request, 'utm_term'),
            'utm_content' => $this->queryValue($request, 'utm_content'),
            'device_type_id' => null,
        ]);

        $this->queueCookie($session->session_key);

        return $session;
    }

    public function extractSessionKey(Request $request): ?string
    {
        $sessionKey = $request->cookie(config('analytics.cookies.session'));

        return is_string($sessionKey) && Str::isUuid($sessionKey) ? $sessionKey : null;
    }

    private function findExistingSession(Request $request): ?Session
    {
        $sessionKey = $this->extractSessionKey($request);

        if (! $sessionKey) {
            return null;
        }

        return Session::query()
            ->where('session_key', $sessionKey)
            ->first();
    }

    private function isExpired(Session $session, $now): bool
    {
        $inactivityThreshold = $now->copy()->subMinutes((int) config('analytics.session.inactivity_timeout_minutes', 30));
        $absoluteThreshold = $now->copy()->subMinutes((int) config('analytics.session.absolute_timeout_minutes', 240));

        if ($session->ended_at !== null) {
            return true;
        }

        if ($session->started_at && $session->started_at->lt($absoluteThreshold)) {
            return true;
        }

        return optional($session->updated_at)->lt($inactivityThreshold) ?? false;
    }

    private function resolvePageId(?string $pageKey): ?int
    {
        if (! $pageKey) {
            return null;
        }

        return $this->catalogService->ensurePage($pageKey)->id;
    }

    private function queryValue(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key, ''));

        return $value === '' ? null : $value;
    }

    private function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        return $url === '' ? null : $url;
    }

    private function queueCookie(string $sessionKey): void
    {
        Cookie::queue(Cookie::make(
            name: config('analytics.cookies.session'),
            value: $sessionKey,
            minutes: (int) config('analytics.cookies.minutes'),
            path: config('analytics.cookies.path', '/'),
            domain: config('analytics.cookies.domain'),
            secure: (bool) config('analytics.cookies.secure'),
            httpOnly: (bool) config('analytics.cookies.http_only', false),
            raw: false,
            sameSite: config('analytics.cookies.same_site', 'lax'),
        ));
    }
}
