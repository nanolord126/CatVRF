<?php

declare(strict_types=1);

namespace App\Domains\Communication\Services;

use App\Domains\Communication\DTOs\SendMessageDto;
use App\Domains\Communication\DTOs\CreateChannelDto;
use App\Domains\Communication\Models\CommunicationChannel;
use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Events\MessageSentEvent;
use App\Domains\Communication\Jobs\DispatchMessageJob;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

/**
 * Layer 3: Main Communication Service.
 * Orchestrates message sending across all channels (email, sms, push, in_app, telegram).
 *
 * Canon: final readonly, DI only, FraudControlService::check() + DB::transaction() on all mutations.
 */
final readonly class CommunicationService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private IdempotencyService $idempotency,
        private DatabaseManager $db,
        private LogManager $logger,
    ) {}

    /**
     * Send a message through the specified channel.
     * Idempotent: same idempotency_key returns cached result.
     */
    public function send(SendMessageDto $dto): Message
    {
        // Idempotency check
        if ($dto->idempotencyKey !== null) {
            $existing = $this->idempotency->check(
                operation: 'communication_send',
                idempotencyKey: $dto->idempotencyKey,
                payload: $dto->toArray(),
                tenantId: $dto->tenantId,
            );
            if (!empty($existing['message_id'])) {
                return Message::findOrFail($existing['message_id']);
            }
        }

        $this->fraud->check(
            userId: $dto->recipientId,
            operationType: 'communication_send',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): Message {
            $message = Message::create($dto->toArray());

            // Dispatch async delivery job
            DispatchMessageJob::dispatch($message->id, $dto->channelType, $dto->correlationId)
                ->onQueue('notifications');

            if ($dto->idempotencyKey !== null) {
                $this->idempotency->record(
                    operation: 'communication_send',
                    idempotencyKey: $dto->idempotencyKey,
                    payload: $dto->toArray(),
                    response: ['message_id' => $message->id],
                    tenantId: $dto->tenantId,
                );
            }

            event(new MessageSentEvent($message, $dto->correlationId));

            $this->audit->log('message_sent', [
                'subject_type' => Message::class,
                'subject_id' => $message->id,
                'new' => $dto->toArray(),
            ], $dto->correlationId);

            $this->logger->channel('audit')->info('Message sent', [
                'message_id'     => $message->id,
                'channel_type'   => $dto->channelType,
                'recipient_type' => $dto->recipientType,
                'correlation_id' => $dto->correlationId,
                'tenant_id'      => $dto->tenantId,
            ]);

            return $message;
        });
    }

    /**
     * Create or update a communication channel.
     */
    public function createChannel(CreateChannelDto $dto): CommunicationChannel
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'communication_channel_create',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): CommunicationChannel {
            $channel = CommunicationChannel::create($dto->toArray());

            $this->audit->log('channel_created', [
                'subject_type' => CommunicationChannel::class,
                'subject_id' => $channel->id,
                'new' => $dto->toArray(),
            ], $dto->correlationId);

            $this->logger->channel('audit')->info('Communication channel created', [
                'channel_id'     => $channel->id,
                'type'           => $dto->type,
                'correlation_id' => $dto->correlationId,
                'tenant_id'      => $dto->tenantId,
            ]);

            return $channel;
        });
    }

    /**
     * Disable a communication channel.
     */
    public function disableChannel(int $channelId, string $correlationId): void
    {
        $this->db->transaction(function () use ($channelId, $correlationId): void {
            $channel = CommunicationChannel::findOrFail($channelId);

            $old = $channel->toArray();
            $channel->update(['status' => 'disabled']);

            $this->audit->log('channel_disabled', [
                'subject_type' => CommunicationChannel::class,
                'subject_id' => $channelId,
                'old' => $old,
                'new' => ['status' => 'disabled'],
            ], $correlationId);

            $this->logger->channel('audit')->info('Communication channel disabled', [
                'channel_id'     => $channelId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Mark message as delivered (called from webhook).
     */
    public function markDelivered(int $messageId, string $correlationId): void
    {
        $this->db->transaction(function () use ($messageId, $correlationId): void {
            Message::where('id', $messageId)
                ->update([
                    'status'       => 'delivered',
                    'delivered_at' => now(),
                ]);

            $this->logger->channel('audit')->info('Message delivered', [
                'message_id'     => $messageId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Mark message as read.
     */
    public function markRead(int $messageId, string $correlationId): void
    {
        $this->db->transaction(function () use ($messageId, $correlationId): void {
            Message::where('id', $messageId)
                ->whereNull('read_at')
                ->update([
                    'status'  => 'read',
                    'read_at' => now(),
                ]);
        });
    }

    /**
     * List messages for a recipient with pagination.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listForRecipient(int $recipientId, int $perPage = 20): mixed
    {
        return Message::where('recipient_id', $recipientId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Log a channel update in the audit trail.
     *
     * @param  array<string, mixed>  $old
     */
    public function auditChannelUpdate(
        CommunicationChannel $channel,
        array                $old,
        string               $correlationId,
    ): void {
        $this->audit->log(
            'channel_updated',
            [
                'subject_type' => CommunicationChannel::class,
                'subject_id' => $channel->id,
                'old' => $old,
                'new' => $channel->toArray(),
            ],
            $correlationId,
        );

        $this->logger->channel('audit')->info('Communication channel updated', [
            'channel_id'     => $channel->id,
            'correlation_id' => $correlationId,
        ]);
    }
}
