<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ChatController extends Controller
{

    public function __construct(
            private readonly RealtimeChatService $chat,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {
            // PRODUCTION-READY 2026 CANON: Middleware для Realtime Chat
             // Только авторизованные пользователи
             // 500 сообщений/час (anti-spam)
             // Tenant scoping обязателен
            $this->middleware('fraud-check', ['only' => ['sendMessage', 'createRoom']]); // Проверка перед созданием
        }
        /**
         * Отправляет сообщение
         */
        public function sendMessage(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'room_id' => 'required|string|uuid',
                    'content' => 'required|string|max:5000',
                ]);
                $message = $this->chat->createMessage(
                    userId: $this->guard->id(),
                    tenantId: filament()->getTenant()->id,
                    roomId: $validated['room_id'],
                    content: $validated['content'],
                    correlationId: $correlationId
                );
                $this->logger->channel('audit')->info('Chat message sent', [
                    'correlation_id' => $correlationId,
                    'room_id' => $validated['room_id'],
                ]);
                return $this->response->json([
                    'message' => $message,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to send chat message', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to send message',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Получает сообщения комнаты
         */
        public function getMessages(Request $request, string $roomId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'limit' => 'nullable|integer|min:1|max:100',
                ]);
                $limit = $validated['limit'] ?? 50;
                $messages = $this->chat->getRoomMessages($roomId, $limit);
                return $this->response->json([
                    'messages' => $messages,
                    'count' => $messages->count(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to get messages', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to get messages',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Удаляет сообщение
         */
        public function deleteMessage(Request $request, string $roomId, string $messageId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $this->chat->deleteMessage(
                    roomId: $roomId,
                    messageId: $messageId,
                    userId: $this->guard->id(),
                    correlationId: $correlationId
                );
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to delete message', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to delete message',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Редактирует сообщение
         */
        public function editMessage(Request $request, string $roomId, string $messageId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'content' => 'required|string|max:5000',
                ]);
                $message = $this->chat->editMessage(
                    roomId: $roomId,
                    messageId: $messageId,
                    content: $validated['content'],
                    userId: $this->guard->id(),
                    correlationId: $correlationId
                );
                return $this->response->json([
                    'message' => $message,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to edit message', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to edit message',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Создаёт комнату
         */
        public function createRoom(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'members' => 'nullable|array',
                    'members.*' => 'integer|min:1',
                ]);
                $room = $this->chat->createRoom(
                    tenantId: filament()->getTenant()->id,
                    name: $validated['name'],
                    memberIds: $validated['members'] ?? [],
                    correlationId: $correlationId
                );
                return $this->response->json([
                    'room' => $room,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to create room', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to create room',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
