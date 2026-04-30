<?php

declare(strict_types=1);

namespace App\Domains\Communication\Services;

use Psr\Log\LoggerInterface;

use App\Domains\Communication\DTOs\SendChatMessageDto;
use App\Domains\Communication\Models\ChatRoom;
use App\Domains\Communication\Models\ChatMessage;
use App\Domains\Communication\Events\ChatMessageSentEvent;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Carbon\CarbonImmutable;

/**
 * Layer 3: Chat Service.
 * Real-time chat rooms: support, order discussions, B2B deal negotiations.
 *
 * Canon: final readonly, DI only, FraudControlService::check() + $this->db->transaction().
 */
final readonly class ChatService
{
    public function __construct(private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,) {}

    /**
     * Create a new chat room.
     */
    public function createRoom(
        int $tenantId,
        string $type,
        string $title,
        string $correlationId,
        ?string $entityType = null,
        ?int $entityId = null,
    ): ChatRoom {
        return $this->db->transaction(function () use (
            $tenantId,
            $type,
            $title,
            $correlationId,
            $entityType,
            $entityId
        ): ChatRoom {
            $room = ChatRoom::create([
                'tenant_id'      => $tenantId,
                'type'           => $type,
                'title'          => $title,
                'context_type'   => $entityType,
                'context_id'     => $entityId,
                'participants'   => [],
                'status'         => 'active',
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('chat_room_created', [
                'subject_type' => ChatRoom::class,
                'subject_id' => $room->id,
                'old' => [],
                'new' => ['type' => $type, 'title' => $title],
            ], $correlationId);

            $this->logger->channel('audit')->$this->logger->info('Chat room created', [
                'room_id'        => $room->id,
                'type'           => $type,
                'correlation_id' => $correlationId,
                'tenant_id'      => $tenantId,
            ]);

            return $room;
        });
    }

    /**
     * Send a message in a chat room.
     */
    public function sendMessage(SendChatMessageDto $dto): ChatMessage
    {
        $this->fraud->check(
            userId: $dto->senderId,
            operationType: 'chat_send_message',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): ChatMessage {
            $room = ChatRoom::findOrFail($dto->roomId);

            if ($room->status !== 'active') {
                throw new \RuntimeException('Chat room is closed');
            }

            $chatMessage = ChatMessage::create($dto->toArray());

            // Real-time broadcast via Laravel Echo
            broadcast(new ChatMessageSentEvent($chatMessage, $dto->correlationId))
                ->toOthers();

            $this->logger->channel('audit')->$this->logger->info('Chat message sent', [
                'room_id'        => $dto->roomId,
                'sender_id'      => $dto->senderId,
                'correlation_id' => $dto->correlationId,
                'tenant_id'      => $dto->tenantId,
            ]);

            return $chatMessage;
        });
    }

    /**
     * Mark all messages in a room as read for a user.
     */
    public function markRoomAsRead(int $roomId, int $userId): void
    {
        ChatMessage::where('chat_room_id', $roomId)
            ->where('sender_user_id', '!=', $userId)
            ->where('is_read', false)
            ->where('is_deleted', false)
            ->update(['is_read' => true, 'read_at' => CarbonImmutable::now()]);
    }

    /**
     * Close a chat room.
     */
    public function closeRoom(int $roomId, string $correlationId): void
    {
        $this->db->transaction(function () use ($roomId, $correlationId): void {
            ChatRoom::where('id', $roomId)->update([
                'status'    => 'closed',
                'closed_at' => CarbonImmutable::now(),
            ]);

            $this->logger->channel('audit')->$this->logger->info('Chat room closed', [
                'room_id'        => $roomId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Paginated message history for a room.
     *
     * @return LengthAwarePaginator
     */
    public function getRoomHistory(int $roomId, int $perPage = 50): mixed
    {
        return ChatMessage::where('chat_room_id', $roomId)
            ->where('is_deleted', false)
            ->with('sender:id,name,avatar')
            ->orderBy('created_at')
            ->paginate($perPage);
    }
}
