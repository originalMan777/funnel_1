<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class VisitorResolver
{
    /**
     * Resolve or create the anonymous analytics visitor and refresh its cookie.
     */
    public function resolve(Request $request): Visitor
    {
        $now = now();
        $visitorKey = $this->extractVisitorKey($request) ?? (string) Str::uuid();
        $userAgentHash = $this->hashUserAgent($request->userAgent());

        $visitor = Visitor::query()->firstOrNew([
            'visitor_key' => $visitorKey,
        ]);

        if (! $visitor->exists) {
            $visitor->first_seen_at = $now;
            $visitor->first_user_agent_hash = $userAgentHash;
        }

        $visitor->last_seen_at = $now;
        $visitor->latest_user_agent_hash = $userAgentHash;
        $visitor->save();

        $this->queueCookie(config('analytics.cookies.visitor'), $visitor->visitor_key);

        return $visitor;
    }

    public function extractVisitorKey(Request $request): ?string
    {
        $visitorKey = $request->cookie(config('analytics.cookies.visitor'));

        return is_string($visitorKey) && Str::isUuid($visitorKey) ? $visitorKey : null;
    }

    private function hashUserAgent(?string $userAgent): ?string
    {
        $userAgent = trim((string) $userAgent);

        return $userAgent === '' ? null : hash('sha256', $userAgent);
    }

    private function queueCookie(string $name, string $value): void
    {
        Cookie::queue(Cookie::make(
            name: $name,
            value: $value,
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
