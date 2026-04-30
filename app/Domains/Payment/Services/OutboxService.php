<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Models\OutboxMessage;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Outbox Service - reliable webhook delivery with retry and idempotency.
 *
 * Implements the Outbox pattern for reliable event delivery to external systems.
 * Ensures at-least-once delivery with idempotency guarantees.
 */
final readonly class OutboxService
{
    private const int MAX_RETRY_ATTEMPTS = 5;
    private const int RETRY_DELAY_SECONDS = 60;
    private const array EXPONENTIAL_BACKOFF_BASES = [60, 300, 900, 3600, 7200]; // 1min, 5min, 15min, 1h, 2h

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Store outbox message for delivery.
     */
    public function store(
        string $eventType,
        string $targetUrl,
        array $payload,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
        ?\DateTime $deliverAfter = null,
    ): OutboxMessage {
        $correlationId ??= Str::uuid()->toString();
        $idempotencyKey ??= Str::uuid()->toString();

        $this->logger->info('Storing outbox message', [
            'event_type' => $eventType,
            'target_url' => $targetUrl,
            'idempotency_key' => $idempotencyKey,
            'correlation_id' => $correlationId,
        ]);

        // Check for duplicate idempotency key
        $existing = OutboxMessage::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            $this->logger->info('Duplicate outbox message detected', [
                'existing_id' => $existing->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            return $existing;
        }

        $message = OutboxMessage::create([
            'uuid' => Str::uuid()->toString(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
            'event_type' => $eventType,
            'target_url' => $targetUrl,
            'payload' => $payload,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
            'attempt_count' => 0,
            'deliver_after' => $deliverAfter,
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

    /**
     * Process pending outbox messages.
     */
    public function processPending(): int
    {
        $messages = OutboxMessage::where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('deliver_after')
                    ->orWhere('deliver_after', '<=', now());
            })
            ->where('attempt_count', '<', self::MAX_RETRY_ATTEMPTS)
            ->orderBy('created_at')
            ->limit(100)
            ->get();

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

    /**
     * Deliver single outbox message.
     */
    public function deliver(OutboxMessage $message): bool
    {
        $this->logger->info('Delivering outbox message', [
            'message_id' => $message->id,
            'event_type' => $message->event_type,
            'target_url' => $message->target_url,
            'attempt' => $message->attempt_count + 1,
        ]);

        return $this->db->transaction(function () use ($message) {
            // Update attempt count
            $message->increment('attempt_count');
            $message->update([
                'last_attempt_at' => now(),
            ]);

            try {
                // Make HTTP request
                $response = \Illuminate\Support\Facades\Http::timeout(30)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Idempotency-Key' => $message->idempotency_key,
                        'X-Correlation-ID' => $message->correlation_id,
                    ])
                    ->post($message->target_url, [
                        'event_type' => $message->event_type,
                        'payload' => $message->payload,
                        'timestamp' => $message->created_at->toIso8601String(),
                        'idempotency_key' => $message->idempotency_key,
                    ]);

                if ($response->successful()) {
                    $message->update([
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

                // Handle failure with retry
                $this->handleFailure($message, $response->status(), $response->body());

                return false;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $this->handleFailure($message, null, $e->getMessage());

                return false;
            } catch (\Throwable $e) {
                $this->handleFailure($message, null, $e->getMessage());

                return false;
            }
        });
    }

    /**
     * Handle delivery failure with exponential backoff.
     */
    private function handleFailure(OutboxMessage $message, ?int $statusCode, ?string $errorMessage): void
    {
        $attemptCount = $message->attempt_count;

        if ($attemptCount >= self::MAX_RETRY_ATTEMPTS) {
            $message->update([
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

        // Calculate next delivery time with exponential backoff
        $backoffIndex = min($attemptCount - 1, count(self::EXPONENTIAL_BACKOFF_BASES) - 1);
        $backoffSeconds = self::EXPONENTIAL_BACKOFF_BASES[$backoffIndex];
        $nextDelivery = now()->addSeconds($backoffSeconds);

        $message->update([
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

    /**
     * Get failed messages for manual processing.
     */
    public function getFailedMessages(): \Illuminate\Database\Eloquent\Collection
    {
        return OutboxMessage::where('status', 'failed')
            ->orderBy('failed_at', 'desc')
            ->get();
    }

    /**
     * Retry failed message manually.
     */
    public function retryMessage(int $messageId): bool
    {
        $message = OutboxMessage::findOrFail($messageId);

        if ($message->status !== 'failed') {
            throw new \InvalidArgumentException('Can only retry failed messages');
        }

        $message->update([
            'status' => 'pending',
            'attempt_count' => 0,
            'deliver_after' => now(),
            'failed_at' => null,
        ]);

        return $this->deliver($message);
    }

    /**
     * Clean up old delivered messages.
     */
    public function cleanup(int $daysToKeep = 30): int
    {
        $cutoff = now()->subDays($daysToKeep);

        $deleted = OutboxMessage::where('status', 'delivered')
            ->where('delivered_at', '<', $cutoff)
            ->delete();

        $this->logger->info('Cleaned up old outbox messages', [
            'count' => $deleted,
            'cutoff' => $cutoff->toIso8601String(),
        ]);

        return $deleted;
    }
}
