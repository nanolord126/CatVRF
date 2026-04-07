<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Controllers;


use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

use App\Http\Controllers\Controller;

final class ChatController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    /**
         * Send chat message during stream
         */
        public function send(SendChatMessageRequest $request, string $roomId): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $userId = $request->user()?->id;

                $stream = Stream::where('room_id', $roomId)
                    ->where('status', 'live')
                    ->where('tenant_id', tenant()->id)
                    ->firstOrFail();

                $message = StreamChatMessage::create([
                    'stream_id' => $stream->id,
                    'user_id' => $userId,
                    'message' => $request->string('message')->value(),
                    'message_type' => $request->string('message_type', 'text')->value(),
                    'is_pinned' => false,
                    'moderation_status' => $this->config->get('bloggers.chat.auto_approve') ? 'approved' : 'pending',
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Chat message sent', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'user_id' => $userId,
                    'message_type' => $message->message_type,
                ]);

                // Broadcast via Reverb
                broadcast(new \App\Domains\Content\Bloggers\Events\ChatMessagePosted(
                    roomId: $roomId,
                    message: $message,
                ))->toOthers();

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $message,
                    'status' => $message->moderation_status,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Send message failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Failed to send message',
                    'error' => $e->getMessage(),
                ], 400);
            }
        }

        /**
         * Get stream chat messages
         */
        public function getMessages(string $roomId, Request $request): JsonResponse
        {
            try {
                $limit = min($request->integer('limit', 50), 200);
                $offset = $request->integer('offset', 0);

                $stream = Stream::where('room_id', $roomId)
                    ->where('tenant_id', tenant()->id)
                    ->firstOrFail();

                $messages = StreamChatMessage::where('stream_id', $stream->id)
                    ->where('moderation_status', 'approved')
                    ->with('user')
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->offset($offset)
                    ->get();

                return new \Illuminate\Http\JsonResponse([
                    'data' => $messages,
                    'count' => count($messages),
                    'has_more' => count($messages) === $limit,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Failed to fetch messages',
                ], 500);
            }
        }

        /**
         * Delete message (by author or stream owner)
         */
        public function delete(int $messageId): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $userId = $request->user()?->id;

                $message = StreamChatMessage::with('stream')
                    ->findOrFail($messageId);

                // Check authorization
                if ($message->user_id !== $userId && $message->stream->blogger_id !== $userId) {
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Unauthorized',
                    ], 403);
                }

                $message->delete();

                $this->logger->info('Chat message deleted', [
                    'correlation_id' => $correlationId,
                    'message_id' => $messageId,
                    'user_id' => $userId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'success' => true,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Failed to delete message',
                ], 400);
            }
        }

        /**
         * Pin message (stream owner only, max 3 pinned)
         */
        public function pin(int $messageId): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $blogerId = $request->user()?->id;

                $message = StreamChatMessage::with('stream')
                    ->findOrFail($messageId);

                // Check authorization
                if ($message->stream->blogger_id !== $blogerId) {
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Only stream owner can pin messages',
                    ], 403);
                }

                // Check max pinned limit
                $pinnedCount = StreamChatMessage::where('stream_id', $message->stream_id)
                    ->where('is_pinned', true)
                    ->count();

                if ($pinnedCount >= 3) {
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Maximum 3 pinned messages allowed',
                    ], 400);
                }

                $message->update(['is_pinned' => true]);

                $this->logger->info('Chat message pinned', [
                    'correlation_id' => $correlationId,
                    'message_id' => $messageId,
                    'stream_id' => $message->stream_id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $message,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Failed to pin message',
                ], 400);
            }
        }
}
