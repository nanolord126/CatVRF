<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

final readonly class RealtimeChatService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    ) {}

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
            ?string $correlationId = null
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

                $this->cache->put($messageKey, $message, self::CHAT_TTL);

                // Добавляем в историю комнаты
                $roomHistoryKey = "chat:room:{$roomId}:messages";
                $messages = $this->cache->get($roomHistoryKey, []);
                $messages[] = $messageId;

                // Сохраняем только последние 1000 сообщений
                if (count($messages) > 1000) {
                    $oldestMessageId = array_shift($messages);
                    $this->cache->forget("chat:message:{$roomId}:{$oldestMessageId}");
                }

                $this->cache->put($roomHistoryKey, $messages, self::CHAT_TTL);

                $this->logger->channel('audit')->debug('Chat message created', [
                    'correlation_id' => $correlationId,
                    'message_id' => $messageId,
                    'room_id' => $roomId,
                    'user_id' => $userId,
                ]);

                return $message;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to create chat message', [
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
            $messageIds = $this->cache->get($roomHistoryKey, []);

            $messages = collect();

            // Берём последние $limit сообщений
            $recentMessageIds = array_slice($messageIds, -$limit);

            foreach ($recentMessageIds as $messageId) {
                $messageKey = "chat:message:{$roomId}:{$messageId}";
                $message = $this->cache->get($messageKey);

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
            ?string $correlationId = null
        ): bool {
            $correlationId ??= Str::uuid()->toString();

            try {
                $messageKey = "chat:message:{$roomId}:{$messageId}";
                $message = $this->cache->get($messageKey);

                if (!$message) {
                    throw new \RuntimeException("Message not found: {$messageId}");
                }

                if ($message['user_id'] !== $userId) {
                    throw new \RuntimeException('Unauthorized to delete this message');
                }

                $message['deleted'] = true;
                $message['deleted_at'] = now()->toIso8601String();

                $this->cache->put($messageKey, $message, self::CHAT_TTL);

                $this->logger->channel('audit')->info('Chat message deleted', [
                    'correlation_id' => $correlationId,
                    'message_id' => $messageId,
                    'room_id' => $roomId,
                ]);

                return true;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to delete chat message', [
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
            ?string $correlationId = null
        ): array {
            $correlationId ??= Str::uuid()->toString();

            try {
                $messageKey = "chat:message:{$roomId}:{$messageId}";
                $message = $this->cache->get($messageKey);

                if (!$message) {
                    throw new \RuntimeException("Message not found: {$messageId}");
                }

                if ($message['user_id'] !== $userId) {
                    throw new \RuntimeException('Unauthorized to edit this message');
                }

                $message['content'] = $content;
                $message['edited_at'] = now()->toIso8601String();

                $this->cache->put($messageKey, $message, self::CHAT_TTL);

                $this->logger->channel('audit')->info('Chat message edited', [
                    'correlation_id' => $correlationId,
                    'message_id' => $messageId,
                ]);

                return $message;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to edit chat message', [
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
            ?string $correlationId = null
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

                $this->cache->put($roomKey, $room, self::ACTIVE_ROOMS_TTL);

                $this->logger->channel('audit')->info('Chat room created', [
                    'correlation_id' => $correlationId,
                    'room_id' => $roomId,
                    'tenant_id' => $tenantId,
                ]);

                return $room;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to create chat room', [
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
            ?string $correlationId = null
        ): bool {
            $correlationId ??= Str::uuid()->toString();

            try {
                $roomKey = "chat:room:{$roomId}";
                $room = $this->cache->get($roomKey);

                if (!$room) {
                    throw new \RuntimeException("Room not found: {$roomId}");
                }

                if (!in_array($userId, $room['members'])) {
                    $room['members'][] = $userId;
                    $this->cache->put($roomKey, $room, self::ACTIVE_ROOMS_TTL);
                }

                return true;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to add member to room', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
}
