<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Psr\Log\LoggerInterface;

use App\Shared\Infrastructure\Persistence\OutboxMessage;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use App\Services\Metrics\EventMetricsCollector;
use App\Shared\Application\Services\DeadLetterQueueService;
use App\Shared\Infrastructure\Persistence\DeadLetterQueue;

/**
 * Job to publish pending outbox messages to event bus
 * Runs on dedicated queue with proper retry logic
 */
final class PublishOutboxMessagesJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    private const BATCH_SIZE = 100;

    public string $queue = 'default';

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(private readonly BusDispatcher $bus,
        private readonly LoggerInterface $logger,
        private readonly ?string $queueName = null,
        private readonly ?int $priority = null,
        private readonly DatabaseManager $db,
        private readonly Dispatcher $event,
        private readonly LogManager $log,
        private readonly ?EventMetricsCollector $metricsCollector = null,
        private readonly ?DeadLetterQueueService $dlqService = null,) {
        if ($queueName) {
            $this->queue = $queueName;
        }
    }

    public function handle(): void
    {
        $query = OutboxMessage::query();

        if ($this->priority) {
            $query->where('priority', '>=', $this->priority);
        }

        // Order by priority (higher first) and created_at (older first)
        $messages = $query
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit(self::BATCH_SIZE)
            ->lockForUpdate()
            ->get();

        if ($messages->isEmpty()) {
            return;
        }

        $this->db->transaction(function () use ($messages) {
            foreach ($messages as $message) {
                $this->publishMessage($message);
            }
        })->setTransactionTime(CarbonImmutable::now());

        $this->log->$this->logger->info('Outbox messages published', [
            'count' => $messages->count(),
            'queue' => $this->queueName ?? 'all',
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->log->error('PublishOutboxMessagesJob failed', [
            'queue' => $this->queueName,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    private function publishMessage(OutboxMessage $message): void
    {
        $startTime = microtime(true);

        try {
            // Reconstruct event from stored data
            $eventClass = $message->event_type;

            if (! class_exists($eventClass)) {
                $this->handleMissingEventClass($message);

                return;
            }

            // Dispatch event via Laravel event system
            $this->event->dispatch(
                new $eventClass(...$message->payload)
            );

            // Mark as published
            $message->markAsPublished();

            // Store to ClickHouse for long-term storage (async)
            if (class_exists(StoreEventToClickHouseJob::class)) {
                $this->bus->dispatch(new StoreEventToClickHouseJob(
                    outboxId: $message->id,
                    eventType: $message->event_type,
                    payload: $message->payload,
                    correlationId: $message->correlation_id,
                    userId: $message->user_id,
                    tenantId: $message->tenant_id,
                ));
            }

            // Record metrics
            $durationMs = (microtime(true) - $startTime) * 1000;
            if ($this->metricsCollector !== null) {
                $this->metricsCollector->recordEventPublished($message->event_type, $message->queue ?? 'unknown');
                $this->metricsCollector->recordEventProcessingTime($message->event_type, $durationMs);
            }

        } catch (\Throwable $e) {
            $this->log->error('Failed to publish outbox message', [
                'message_id' => $message->id,
                'event_type' => $message->event_type,
                'error' => $e->getMessage(),
                'attempts' => $message->processing_attempts + 1,
            ]);

            $message->markAsFailed($e->getMessage());

            // Record failed metric
            if ($this->metricsCollector !== null) {
                $this->metricsCollector->recordEventFailed($message->event_type, 'processing_error');
            }

            // If max retries exceeded, move to dead letter queue
            if (! $message->shouldRetry($this->tries)) {
                $this->moveToDeadLetterQueue($message);
            } else {
                if ($this->metricsCollector !== null) {
                    $this->metricsCollector->recordEventRetried($message->event_type);
                }
            }
        }
    }

    private function handleMissingEventClass(OutboxMessage $message): void
    {
        $this->log->warning('Event class not found, marking as failed', [
            'message_id' => $message->id,
            'event_type' => $message->event_type,
        ]);

        $message->markAsFailed('Event class not found: '.$message->event_type);

        if ($this->metricsCollector !== null) {
            $this->metricsCollector->recordEventFailed($message->event_type, 'class_not_found');
        }
    }

    private function moveToDeadLetterQueue(OutboxMessage $message): void
    {
        if ($this->dlqService !== null) {
            $this->dlqService->moveToDeadLetterQueue(
                outboxMessage: $message,
                errorMessage: $message->last_error ?? 'Unknown error',
                failureReason: DeadLetterQueue::FAILURE_MAX_RETRIES,
            );
        }
    }
}
