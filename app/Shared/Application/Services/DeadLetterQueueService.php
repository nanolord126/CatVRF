<?php

declare(strict_types=1);

namespace App\Shared\Application\Services;

use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Carbon\CarbonImmutable;
use App\Shared\Infrastructure\Persistence\DeadLetterQueue;
use App\Shared\Infrastructure\Persistence\OutboxMessage;

/**
 * Service for managing Dead Letter Queue
 */
final readonly class DeadLetterQueueService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function moveToDeadLetterQueue(
        OutboxMessage $outboxMessage,
        string $errorMessage,
        string $failureReason = DeadLetterQueue::FAILURE_MAX_RETRIES,
    ): DeadLetterQueue {
        return $this->db->transaction(function () use ($outboxMessage, $errorMessage, $failureReason) {
            $dlq = DeadLetterQueue::create([
                'id' => $outboxMessage->id,
                'original_outbox_id' => $outboxMessage->id,
                'event_type' => $outboxMessage->event_type,
                'payload' => $outboxMessage->payload,
                'correlation_id' => $outboxMessage->correlation_id,
                'user_id' => $outboxMessage->user_id,
                'tenant_id' => $outboxMessage->tenant_id,
                'error_message' => $errorMessage,
                'error_trace' => null,
                'retry_count' => $outboxMessage->processing_attempts,
                'failure_reason' => $failureReason,
                'failed_at' => CarbonImmutable::now(),
                'is_processed' => false,
            ]);

            // Mark original outbox message as moved to DLQ
            $outboxMessage->update([
                'status' => OutboxMessage::STATUS_FAILED,
                'last_error' => "Moved to DLQ: {$failureReason}",
            ]);

            $this->log->warning('Event moved to Dead Letter Queue', [
                'dlq_id' => $dlq->id,
                'original_outbox_id' => $outboxMessage->id,
                'event_type' => $outboxMessage->event_type,
                'failure_reason' => $failureReason,
                'error_message' => $errorMessage,
            ]);

            // Record DLQ metric
            $metrics->recordDLQEvent($outboxMessage->event_type, $failureReason);

            return $dlq;
        });
    }

    public function retryEvent(string $dlqId): bool
    {
        $dlq = DeadLetterQueue::findOrFail($dlqId);

        if ($dlq->is_processed) {
            $this->log->warning('Cannot retry processed DLQ event', ['dlq_id' => $dlqId]);
            return false;
        }

        return $this->db->transaction(function () use ($dlq) {
            // Recreate outbox message
            $outbox = OutboxMessage::create([
                'id' => $dlq->id,
                'event_type' => $dlq->event_type,
                'payload' => $dlq->payload,
                'correlation_id' => $dlq->correlation_id,
                'causation_id' => "dlq-retry:{$dlq->id}",
                'user_id' => $dlq->user_id,
                'tenant_id' => $dlq->tenant_id,
                'status' => OutboxMessage::STATUS_PENDING,
                'processing_attempts' => 0,
                'priority' => OutboxMessage::PRIORITY_HIGH,
                'queue' => 'default',
            ]);

            // Mark DLQ as processed
            $dlq->markAsProcessed('manual_retry');

            $this->log->$this->logger->info('Event retried from Dead Letter Queue', [
                'dlq_id' => $dlq->id,
                'new_outbox_id' => $outbox->id,
                'event_type' => $dlq->event_type,
            ]);

            return true;
        });
    }

    public function getUnprocessedEvents(int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return DeadLetterQueue::unprocessed()
            ->orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getStats(): array
    {
        return [
            'total' => DeadLetterQueue::count(),
            'unprocessed' => DeadLetterQueue::unprocessed()->count(),
            'processed' => DeadLetterQueue::where('is_processed', true)->count(),
            'recent_24h' => DeadLetterQueue::recent(1)->count(),
            'recent_7d' => DeadLetterQueue::recent(7)->count(),
            'by_failure_reason' => DeadLetterQueue::selectRaw('failure_reason, COUNT(*) as count')
                ->groupBy('failure_reason')
                ->get()
                ->pluck('count', 'failure_reason')
                ->toArray(),
            'by_event_type' => DeadLetterQueue::selectRaw('event_type, COUNT(*) as count')
                ->groupBy('event_type')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'event_type')
                ->toArray(),
        ];
    }
}
