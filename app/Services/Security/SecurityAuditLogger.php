<?php

namespace App\Services\Security;

use App\Models\SecurityAuditLog;
use App\Services\Logging\StructuredEventLogger;
use Illuminate\Http\Request;

class SecurityAuditLogger
{
    public function __construct(
        private readonly StructuredEventLogger $logger,
    ) {}

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

        $this->logger->info('security_audit', 'security_audit', 'security_audit_event', [
            'request' => $request,
            'user_id' => $userId,
            'actor_type' => $request?->user() ? 'user' : 'system',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'outcome' => 'recorded',
            'reason' => $event,
            'context' => $context,
        ]);
    }
}
