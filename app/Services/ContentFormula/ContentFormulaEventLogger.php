<?php

namespace App\Services\ContentFormula;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ContentFormulaEventLogger
{
    public function info(string $event, ?Request $request = null, array $context = []): void
    {
        $this->write('info', $event, $request, $context);
    }

    public function warning(string $event, ?Request $request = null, array $context = []): void
    {
        $this->write('warning', $event, $request, $context);
    }

    public function error(string $event, ?Request $request = null, array $context = []): void
    {
        $this->write('error', $event, $request, $context);
    }

    public function selectedGroupCounts(mixed $groups): array
    {
        if (!is_array($groups)) {
            return [];
        }

        return collect($groups)
            ->mapWithKeys(function ($items, $key) {
                return [is_string($key) ? $key : (string) $key => is_array($items) ? count($items) : 0];
            })
            ->all();
    }

    public function safeExceptionMessage(Throwable $exception): string
    {
        return mb_substr(trim($exception->getMessage()), 0, 500);
    }

    protected function write(string $level, string $event, ?Request $request, array $context): void
    {
        $payload = array_merge([
            'event' => $event,
            'user_id' => $request?->user()?->id,
            'route_name' => $request?->route()?->getName(),
            'http_method' => $request?->getMethod(),
            'ip_address' => $request?->ip(),
            'request_id' => $request?->headers->get('X-Request-Id') ?: $request?->attributes->get('request_id'),
            'timestamp' => now()->toIso8601String(),
        ], $context);

        Log::channel(config('logging.default'))->{$level}($event, $payload);
    }
}
