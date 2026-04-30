<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use App\Shared\Application\Services\DeadLetterQueueService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

/**
 * Job to process Dead Letter Queue
 * Runs periodically to retry failed events
 */
final class ProcessDeadLetterQueueJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public string $queue = 'default';

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $batchSize = 50,
        private readonly bool $autoRetry = false,
        private readonly LogManager $log,
        private readonly DeadLetterQueueService $dlqService,
        public readonly string $correlationId = '') {}

    public function handle(): void
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        $dlqService = $this->dlqService;

        $events = $dlqService->getUnprocessedEvents($this->batchSize);

        if ($events->isEmpty()) {
            $this->log->$this->logger->info('No unprocessed events in Dead Letter Queue');

            return;
        }

        $retried = 0;
        $failed = 0;

        foreach ($events as $event) {
            if ($this->autoRetry) {
                try {
                    if ($dlqService->retryEvent($event->id)) {
                        $retried++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $this->log->error('Failed to retry event from DLQ', [
                        'dlq_id' => $event->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }
        }

        $this->log->$this->logger->info('Dead Letter Queue processed', [
            'total' => $events->count(),
            'retried' => $retried,
            'failed' => $failed,
            'auto_retry' => $this->autoRetry,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->log->error('ProcessDeadLetterQueueJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
