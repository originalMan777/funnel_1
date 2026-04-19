<?php

namespace App\Jobs;

use App\Services\Communications\CommunicationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;
use Throwable;

class ProcessCommunicationEventJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries;

    public function __construct(
        public readonly int $communicationEventId,
    ) {
        $this->tries = (int) config('communications.retry_tries', 3);
        $this->onConnection((string) config('communications.queue_connection', 'database'));
        $this->onQueue((string) config('communications.queue', 'communications'));
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return array_map(
            static fn (mixed $seconds): int => (int) $seconds,
            (array) config('communications.retry_backoff_seconds', [60, 300, 900]),
        );
    }

    public function handle(CommunicationService $communicationService): void
    {
        $jobId = is_object($this->job) && method_exists($this->job, 'uuid')
            ? $this->job->uuid()
            : null;

        $processedWithoutFailures = $communicationService->processEvent(
            $this->communicationEventId,
            $jobId,
        );

        if (! $processedWithoutFailures) {
            throw new RuntimeException("Communication event {$this->communicationEventId} did not fully process.");
        }
    }

    public function failed(Throwable $exception): void
    {
        $jobId = is_object($this->job) && method_exists($this->job, 'uuid')
            ? $this->job->uuid()
            : null;

        app(CommunicationService::class)->markProcessingEventAsFailed(
            $this->communicationEventId,
            $jobId,
            $exception,
        );
    }
}
