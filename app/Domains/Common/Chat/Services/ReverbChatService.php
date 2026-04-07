<?php declare(strict_types=1);

namespace App\Domains\Common\Chat\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ReverbChatService
{

    private readonly string $correlationId;

        public function __construct(string $correlationId = null,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard)
        {
            $this->correlationId = $correlationId ?? (string) Str::uuid();
        }

        /**
         * Создать диалог между пользователями (или системный саппорт).
         */
        public function createConversation(array $userIds, string $type = 'private', array $metadata = []): Conversation
        {
            return $this->db->transaction(function () use ($userIds, $type, $metadata) {
                $conversation = Conversation::create([
                    'tenant_id' => function_exists('tenant') ? tenant()->id : 1,
                    'type' => $type,
                    'metadata' => $metadata,
                ]);

                $conversation->participants()->sync($userIds);

                $this->logger->info('Chat Conversation created', [
                    'conversation_id' => $conversation->id,
                    'participants' => $userIds,
                    'correlation_id' => $this->correlationId,
                ]);

                return $conversation;
            });
        }

        /**
         * Отправить сообщение в диалог.
         */
        public function sendMessage(string $conversationUuid, int $senderId, string $content, string $type = 'text'): Message
        {
            // 1. Проверка на фрод и частоту сообщений
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'chat_message', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($conversationUuid, $senderId, $content, $type) {
                $conversation = Conversation::where('uuid', $conversationUuid)->firstOrFail();

                // 2. Создание сообщения
                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $senderId,
                    'content' => $content,
                    'type' => $type,
                    'correlation_id' => $this->correlationId,
                ]);

                // 3. Широковещание через Reverb
                event(new MessageSent($message, $this->correlationId));

                $this->logger->info('Chat Message sent', [
                    'message_id' => $message->id,
                    'conversation_id' => $conversation->id,
                    'sender_id' => $senderId,
                    'correlation_id' => $this->correlationId,
                ]);

                return $message;
            });
        }

        /**
         * Пометить сообщения диалога как прочитанные пользователем.
         */
        public function markAsRead(string $conversationUuid, int $userId): void
        {
            $conversation = Conversation::where('uuid', $conversationUuid)->firstOrFail();
            $conversation->participants()->updateExistingPivot($userId, [
                'last_read_at' => Carbon::now(),
            ]);
        }
}
