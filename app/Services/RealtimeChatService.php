<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

final class RealtimeChatService
{
    private const CHAT_TTL = 86400; // 24 hours
    private const ACTIVE_ROOMS_TTL = 3600; // 1 hour

    /**
     * Создаёт сообщение в чате
     */
    public function createMessage(
        int $userId,
        int $tenantId,
        string $roomId,
        string $content,
        string $correlationId = null
    ): array {
        $correlationId ??= Str::uuid()->toString();

        try {
            $messageId = Str::uuid()->toString();
            $messageKey = "chat:message:{$roomId}:{$messageId}";

            $message = [
                'message_id' => $messageId,
                'room_id' => $roomId,
                'user_id' => $userId,
                'content' => $content,
                'created_at' => now()->toIso8601String(),
                'edited_at' => null,
                'deleted' => false,
                'correlation_id' => $correlationId,
            ];

            Cache::put($messageKey, $message, self::CHAT_TTL);

            // Добавляем в историю комнаты
            $roomHistoryKey = "chat:room:{$roomId}:messages";
            $messages = Cache::get($roomHistoryKey, []);
            $messages[] = $messageId;

            // Сохраняем только последние 1000 сообщений
            if (count($messages) > 1000) {
                $oldestMessageId = array_shift($messages);
                Cache::forget("chat:message:{$roomId}:{$oldestMessageId}");
            }

            Cache::put($roomHistoryKey, $messages, self::CHAT_TTL);

            Log::channel('audit')->debug('Chat message created', [
                'correlation_id' => $correlationId,
                'message_id' => $messageId,
                'room_id' => $roomId,
                'user_id' => $userId,
            ]);

            return $message;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create chat message', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Получает сообщения комнаты
     */
    public function getRoomMessages(
        string $roomId,
        int $limit = 50
    ): Collection {
        $roomHistoryKey = "chat:room:{$roomId}:messages";
        $messageIds = Cache::get($roomHistoryKey, []);

        $messages = collect();

        // Берём последние $limit сообщений
        $recentMessageIds = array_slice($messageIds, -$limit);

        foreach ($recentMessageIds as $messageId) {
            $messageKey = "chat:message:{$roomId}:{$messageId}";
            $message = Cache::get($messageKey);

            if ($message && !$message['deleted']) {
                $messages->push($message);
            }
        }

        return $messages;
    }

    /**
     * Удаляет сообщение (soft delete)
     */
    public function deleteMessage(
        string $roomId,
        string $messageId,
        int $userId,
        string $correlationId = null
    ): bool {
        $correlationId ??= Str::uuid()->toString();

        try {
            $messageKey = "chat:message:{$roomId}:{$messageId}";
            $message = Cache::get($messageKey);

            if (!$message) {
                throw new \Exception("Message not found: {$messageId}");
            }

            if ($message['user_id'] !== $userId) {
                throw new \Exception("Unauthorized to delete this message");
            }

            $message['deleted'] = true;
            $message['deleted_at'] = now()->toIso8601String();

            Cache::put($messageKey, $message, self::CHAT_TTL);

            Log::channel('audit')->info('Chat message deleted', [
                'correlation_id' => $correlationId,
                'message_id' => $messageId,
                'room_id' => $roomId,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to delete chat message', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Редактирует сообщение
     */
    public function editMessage(
        string $roomId,
        string $messageId,
        string $content,
        int $userId,
        string $correlationId = null
    ): array {
        $correlationId ??= Str::uuid()->toString();

        try {
            $messageKey = "chat:message:{$roomId}:{$messageId}";
            $message = Cache::get($messageKey);

            if (!$message) {
                throw new \Exception("Message not found: {$messageId}");
            }

            if ($message['user_id'] !== $userId) {
                throw new \Exception("Unauthorized to edit this message");
            }

            $message['content'] = $content;
            $message['edited_at'] = now()->toIso8601String();

            Cache::put($messageKey, $message, self::CHAT_TTL);

            Log::channel('audit')->info('Chat message edited', [
                'correlation_id' => $correlationId,
                'message_id' => $messageId,
            ]);

            return $message;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to edit chat message', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Создаёт чат-комнату
     */
    public function createRoom(
        int $tenantId,
        string $name,
        array $memberIds = [],
        string $correlationId = null
    ): array {
        $correlationId ??= Str::uuid()->toString();

        try {
            $roomId = Str::uuid()->toString();
            $roomKey = "chat:room:{$roomId}";

            $room = [
                'room_id' => $roomId,
                'tenant_id' => $tenantId,
                'name' => $name,
                'members' => $memberIds,
                'created_at' => now()->toIso8601String(),
                'archived' => false,
                'correlation_id' => $correlationId,
            ];

            Cache::put($roomKey, $room, self::ACTIVE_ROOMS_TTL);

            Log::channel('audit')->info('Chat room created', [
                'correlation_id' => $correlationId,
                'room_id' => $roomId,
                'tenant_id' => $tenantId,
            ]);

            return $room;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create chat room', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Добавляет пользователя в комнату
     */
    public function addMember(
        string $roomId,
        int $userId,
        string $correlationId = null
    ): bool {
        $correlationId ??= Str::uuid()->toString();

        try {
            $roomKey = "chat:room:{$roomId}";
            $room = Cache::get($roomKey);

            if (!$room) {
                throw new \Exception("Room not found: {$roomId}");
            }

            if (!in_array($userId, $room['members'])) {
                $room['members'][] = $userId;
                Cache::put($roomKey, $room, self::ACTIVE_ROOMS_TTL);
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to add member to room', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
