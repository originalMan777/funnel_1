<?php

namespace App\Services\Logging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class StructuredEventLogger
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function info(string $channel, string $domain, string $event, array $attributes = []): void
    {
        $this->write('info', $channel, $domain, $event, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function warning(string $channel, string $domain, string $event, array $attributes = []): void
    {
        $this->write('warning', $channel, $domain, $event, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function error(string $channel, string $domain, string $event, array $attributes = []): void
    {
        $this->write('error', $channel, $domain, $event, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function write(string $level, string $channel, string $domain, string $event, array $attributes = []): void
    {
        $request = $attributes['request'] ?? null;
        $entity = $attributes['entity'] ?? null;
        $requestId = $attributes['request_id'] ?? $this->requestId($request);
        $userId = $attributes['user_id'] ?? $request?->user()?->getAuthIdentifier();

        $payload = array_filter([
            'event' => $event,
            'domain' => $domain,
            'severity' => $attributes['severity'] ?? $level,
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'app_name' => config('app.name'),
            'request_id' => $requestId,
            'trace_id' => $attributes['trace_id'] ?? $requestId,
            'job_id' => $attributes['job_id'] ?? null,
            'route_name' => $request?->route()?->getName(),
            'http_method' => $request?->getMethod(),
            'user_id' => $userId,
            'actor_type' => $attributes['actor_type'] ?? $this->actorType($request, $userId),
            'entity_type' => $attributes['entity_type'] ?? $this->entityType($entity),
            'entity_id' => $attributes['entity_id'] ?? $this->entityId($entity),
            'outcome' => $attributes['outcome'] ?? null,
            'reason' => $attributes['reason'] ?? null,
            'context' => $this->sanitizeContext($attributes['context'] ?? []),
        ], static fn (mixed $value): bool => $value !== null);

        Log::channel($channel)->{$level}($event, $payload);
    }

    public function safeExceptionMessage(Throwable $exception): string
    {
        return Str::limit(trim($exception->getMessage()), 500, '');
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        return Arr::whereNotNull(
            collect($context)
                ->map(fn (mixed $value) => $this->sanitizeValue($value))
                ->all()
        );
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        if ($value instanceof Model) {
            return [
                'type' => $value::class,
                'id' => $value->getKey(),
            ];
        }

        if ($value instanceof Throwable) {
            return [
                'class' => $value::class,
                'message' => $this->safeExceptionMessage($value),
            ];
        }

        if (is_array($value)) {
            return Arr::whereNotNull(
                collect($value)
                    ->map(fn (mixed $nested) => $this->sanitizeValue($nested))
                    ->all()
            );
        }

        if (is_string($value)) {
            return Str::limit($value, 500, '');
        }

        return $value;
    }

    private function requestId(?Request $request): ?string
    {
        if (! $request instanceof Request) {
            return null;
        }

        return $request->headers->get('X-Request-Id')
            ?: $request->headers->get('X-Trace-Id')
            ?: $request->attributes->get('request_id');
    }

    private function actorType(?Request $request, mixed $userId): string
    {
        if ($request instanceof Request) {
            return $request->user() ? 'user' : 'guest';
        }

        return $userId !== null ? 'user' : 'system';
    }

    private function entityType(mixed $entity): ?string
    {
        if ($entity instanceof Model) {
            return class_basename($entity);
        }

        return null;
    }

    private function entityId(mixed $entity): mixed
    {
        if ($entity instanceof Model) {
            return $entity->getKey();
        }

        return null;
    }
}
