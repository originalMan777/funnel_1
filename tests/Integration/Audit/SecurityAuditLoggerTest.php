<?php

namespace Tests\Integration\Audit;

use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityAuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_logger_persists_audit_rows_and_writes_to_the_log_channel(): void
    {
        Log::shouldReceive('channel')->once()->with(config('logging.default'))->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function (string $message, array $context): bool {
            return $message === 'security_audit_event'
                && $context['event'] === 'audit_test_event'
                && $context['entity_type'] === 'popup_lead'
                && $context['entity_id'] === 42;
        });

        $user = User::factory()->create();
        $request = Request::create('/audit-test', 'POST', server: [
            'REMOTE_ADDR' => '203.0.113.5',
            'HTTP_USER_AGENT' => 'Audit Test Agent',
        ]);

        app(SecurityAuditLogger::class)->log(
            event: 'audit_test_event',
            request: $request,
            userId: $user->id,
            entityType: 'popup_lead',
            entityId: 42,
            context: ['reason' => 'test']
        );

        $this->assertDatabaseHas('security_audit_logs', [
            'user_id' => $user->id,
            'event' => 'audit_test_event',
            'entity_type' => 'popup_lead',
            'entity_id' => 42,
            'ip_address' => '203.0.113.5',
            'user_agent' => 'Audit Test Agent',
        ]);

        $log = SecurityAuditLog::query()->firstOrFail();
        $this->assertSame('test', $log->context['reason']);

    }
}
