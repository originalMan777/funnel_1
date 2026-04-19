<?php

namespace App\Services\Logging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AdminActivityLogger
{
    public function __construct(
        private readonly StructuredEventLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function info(
        string $event,
        Request $request,
        ?Model $entity = null,
        ?string $entityType = null,
        mixed $entityId = null,
        ?string $outcome = 'success',
        ?string $reason = null,
        array $context = [],
    ): void {
        $adminUserId = $request->user()?->getAuthIdentifier();

        $this->logger->info('admin', 'admin', $event, [
            'request' => $request,
            'entity' => $entity,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $adminUserId,
            'actor_type' => 'admin',
            'outcome' => $outcome,
            'reason' => $reason,
            'context' => [
                'admin_user_id' => $adminUserId,
                'action_source' => 'admin_ui',
                ...$context,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(
        string $event,
        Request $request,
        ?Model $entity = null,
        ?string $entityType = null,
        mixed $entityId = null,
        ?string $outcome = 'warning',
        ?string $reason = null,
        array $context = [],
    ): void {
        $adminUserId = $request->user()?->getAuthIdentifier();

        $this->logger->warning('admin', 'admin', $event, [
            'request' => $request,
            'entity' => $entity,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $adminUserId,
            'actor_type' => 'admin',
            'outcome' => $outcome,
            'reason' => $reason,
            'context' => [
                'admin_user_id' => $adminUserId,
                'action_source' => 'admin_ui',
                ...$context,
            ],
        ]);
    }
}
