<?php

namespace App\Services\Security;

use App\Models\SecurityAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityAuditLogger
{
    public function log(
        string $event,
        ?Request $request = null,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $context = []
    ): void {
        $payload = [
            'user_id' => $userId,
            'event' => $event,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'context' => $context,
            'occurred_at' => now(),
        ];

        SecurityAuditLog::create($payload);

        Log::channel(config('logging.default'))->info('security_audit_event', [
            'event' => $event,
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $request?->ip(),
            'occurred_at' => now()->toIso8601String(),
            'context' => $context,
        ]);
    }
}
