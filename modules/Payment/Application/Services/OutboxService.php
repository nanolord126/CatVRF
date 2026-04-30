<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\UuidInterface;
use Modules\Payment\Domain\Entities\OutboxMessage;
use Modules\Payment\Domain\Repositories\OutboxMessageRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Outbox Service - reliable webhook delivery with retry and idempotency.
 */
final readonly class OutboxService
{
    private const int MAX_RETRY_ATTEMPTS = 5;
    private const array EXPONENTIAL_BACKOFF_BASES = [60, 300, 900, 3600, 7200];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
        private readonly OutboxMessageRepositoryInterface $outboxRepository,
        private readonly UuidInterface $uuid,
    ) {}

    public function store(
        string $eventType,
        string $targetUrl,
        array $payload,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
        ?\DateTime $deliverAfter = null,
    ): OutboxMessage {
        $correlationId ??= $this->uuid->toString();
        $idempotencyKey ??= $this->uuid->toString();

        $this->logger->info('Storing outbox message', [
            'event_type' => $eventType,
            'target_url' => $targetUrl,
            'idempotency_key' => $idempotencyKey,
            'correlation_id' => $correlationId,
        ]);

        $existing = $this->outboxRepository->findByIdempotencyKey($idempotencyKey);
        if ($existing) {
            $this->logger->info('Duplicate outbox message detected', [
                'existing_id' => $existing->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            return $existing;
        }

        $message = $this->outboxRepository->create([
            'uuid' => $this->uuid->toString(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
            'event_type' => $eventType,
            'target_url' => $targetUrl,
            'payload' => $payload,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
            'attempt_count' => 0,
            'deliver_after' => $deliverAfter ? CarbonImmutable::instance($deliverAfter) : null,
            'correlation_id' => $correlationId,
            'metadata' => [
                'created_at' => now()->toIso8601String(),
            ],
        ]);

        $this->audit->log(
            action: 'outbox_message_stored',
            subjectType: OutboxMessage::class,
            subjectId: $message->id,
            newValues: [
                'event_type' => $eventType,
                'target_url' => $targetUrl,
            ],
            correlationId: $correlationId,
        );

        return $message;
    }

    public function processPending(): int
    {
        $messages = $this->outboxRepository->findPendingForDelivery(100);
        $processed = 0;

        foreach ($messages as $message) {
            try {
                $this->deliver($message);
                $processed++;
            } catch (\Throwable $e) {
                $this->logger->error('Failed to deliver outbox message', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    public function deliver(OutboxMessage $message): bool
    {
        $this->logger->info('Delivering outbox message', [
            'message_id' => $message->id,
            'event_type' => $message->eventType,
            'target_url' => $message->targetUrl,
            'attempt' => $message->attemptCount + 1,
        ]);

        return $this->db->transaction(function () use ($message) {
            $updated = $this->outboxRepository->update($message->id, [
                'attempt_count' => $message->attemptCount + 1,
                'last_attempt_at' => now(),
            ]);

            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Idempotency-Key' => $message->idempotencyKey,
                        'X-Correlation-ID' => $message->correlationId,
                    ])
                    ->post($message->targetUrl, [
                        'event_type' => $message->eventType,
                        'payload' => $message->payload,
                        'timestamp' => $message->createdAt->toIso8601String(),
                        'idempotency_key' => $message->idempotencyKey,
                    ]);

                if ($response->successful()) {
                    $this->outboxRepository->update($message->id, [
                        'status' => 'delivered',
                        'delivered_at' => now(),
                        'response_code' => $response->status(),
                        'response_body' => $response->body(),
                    ]);

                    $this->logger->info('Outbox message delivered successfully', [
                        'message_id' => $message->id,
                        'response_code' => $response->status(),
                    ]);

                    return true;
                }

                $this->handleFailure($updated, $response->status(), $response->body());
                return false;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $this->handleFailure($updated, null, $e->getMessage());
                return false;
            } catch (\Throwable $e) {
                $this->handleFailure($updated, null, $e->getMessage());
                return false;
            }
        });
    }

    private function handleFailure(OutboxMessage $message, ?int $statusCode, ?string $errorMessage): void
    {
        $attemptCount = $message->attemptCount;

        if ($attemptCount >= self::MAX_RETRY_ATTEMPTS) {
            $this->outboxRepository->update($message->id, [
                'status' => 'failed',
                'failed_at' => now(),
                'response_code' => $statusCode,
                'response_body' => $errorMessage,
            ]);

            $this->logger->error('Outbox message failed permanently', [
                'message_id' => $message->id,
                'attempt_count' => $attemptCount,
                'error' => $errorMessage,
            ]);

            return;
        }

        $backoffIndex = min($attemptCount - 1, count(self::EXPONENTIAL_BACKOFF_BASES) - 1);
        $backoffSeconds = self::EXPONENTIAL_BACKOFF_BASES[$backoffIndex];
        $nextDelivery = now()->addSeconds($backoffSeconds);

        $this->outboxRepository->update($message->id, [
            'deliver_after' => $nextDelivery,
            'response_code' => $statusCode,
            'response_body' => $errorMessage,
        ]);

        $this->logger->warning('Outbox message delivery failed, scheduling retry', [
            'message_id' => $message->id,
            'attempt_count' => $attemptCount,
            'next_delivery' => $nextDelivery->toIso8601String(),
            'error' => $errorMessage,
        ]);
    }

    public function getFailedMessages(): array
    {
        return $this->outboxRepository->findFailed();
    }

    public function retryMessage(int $messageId): bool
    {
        $message = $this->outboxRepository->findById($messageId);

        if (!$message || $message->status !== 'failed') {
            throw new \InvalidArgumentException('Can only retry failed messages');
        }

        $this->outboxRepository->update($messageId, [
            'status' => 'pending',
            'attempt_count' => 0,
            'deliver_after' => now(),
            'failed_at' => null,
        ]);

        return $this->deliver($message);
    }

    public function cleanup(int $daysToKeep = 30): int
    {
        $cutoff = now()->subDays($daysToKeep)->toDateTimeImmutable();
        return $this->outboxRepository->deleteDeliveredBefore($cutoff);
    }
}
