<?php

namespace App\Services\ContentFormula;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ContentFormulaSessionService
{
    public function create(array $settings, array $tier): array
    {
        $session = [
            'id' => (string) Str::uuid(),
            'tier' => $tier['name'],
            'settings' => $settings,
            'used_signatures' => [],
            'usage' => [],
            'generated_count' => 0,
            'continue_count' => 0,
            'reset_count' => 0,
            'exhausted' => false,
        ];

        $this->put($session);

        return $session;
    }

    public function get(string $sessionId): ?array
    {
        return Cache::get($this->key($sessionId));
    }

    public function exists(string $sessionId): bool
    {
        return Cache::has($this->key($sessionId));
    }

    public function put(array $session): void
    {
        Cache::put(
            $this->key((string) $session['id']),
            $session,
            now()->addMinutes((int) config('content_formula.session.ttl_minutes', 240))
        );
    }

    protected function key(string $sessionId): string
    {
        return (string) config('content_formula.session.cache_prefix', 'content_formula_session:') . $sessionId;
    }
}
