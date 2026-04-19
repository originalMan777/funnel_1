<?php

namespace Tests\Unit\Logging;

use App\Services\Logging\StructuredEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StructuredEventLoggerTest extends TestCase
{
    public function test_it_builds_consistent_structured_payloads(): void
    {
        Log::shouldReceive('channel')->once()->with('campaigns')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function (string $message, array $context): bool {
            return $message === 'campaign_runner_batch_started'
                && $context['event'] === 'campaign_runner_batch_started'
                && $context['domain'] === 'campaigns'
                && $context['severity'] === 'info'
                && $context['request_id'] === 'req-123'
                && $context['trace_id'] === 'req-123'
                && $context['user_id'] === 7
                && $context['actor_type'] === 'user'
                && $context['entity_type'] === 'campaign_runner'
                && $context['entity_id'] === 'due'
                && $context['outcome'] === 'started'
                && $context['context']['chunk_size'] === 100;
        });

        $request = Request::create('/admin/campaigns/run', 'POST', server: [
            'HTTP_X_REQUEST_ID' => 'req-123',
        ]);
        app(StructuredEventLogger::class)->info('campaigns', 'campaigns', 'campaign_runner_batch_started', [
            'request' => $request,
            'user_id' => 7,
            'actor_type' => 'user',
            'entity_type' => 'campaign_runner',
            'entity_id' => 'due',
            'outcome' => 'started',
            'context' => [
                'chunk_size' => 100,
            ],
        ]);
    }
}
