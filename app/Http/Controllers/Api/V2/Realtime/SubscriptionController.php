<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Realtime;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class SubscriptionController extends Controller
{


    public function __construct(
            private readonly RealtimeService $realtimeService,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly Connection $redis,
    ) {
            parent::__construct();
        }
        /**
         * Subscribe to channel
         * POST /api/v2/realtime/subscribe
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function subscribe(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                $request->validate([
                    'channel' => 'required|string|max:255',
                ]);
                $channel = $request->get('channel');
                $this->realtimeService->subscribe(
                    userId: $this->guard->id() ?? 0,
                    channel: $channel
                );
                $this->logger->channel('audit')->info('Channel subscription', [
                    'user_id' => $this->guard->id(),
                    'channel' => $channel,
                    'action' => 'subscribe',
                    'correlation_id' => $correlationId,
                ]);
                return $this->successResponse(
                    data: [
                        'channel' => $channel,
                        'status' => 'subscribed',
                    ],
                    message: 'Successfully subscribed to channel',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Subscription failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->errorResponse(
                    message: 'Subscription failed',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
        /**
         * Unsubscribe from channel
         * DELETE /api/v2/realtime/unsubscribe
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function unsubscribe(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                $request->validate([
                    'channel' => 'required|string|max:255',
                ]);
                $channel = $request->get('channel');
                $this->realtimeService->unsubscribe(
                    userId: $this->guard->id() ?? 0,
                    channel: $channel
                );
                $this->logger->channel('audit')->info('Channel unsubscription', [
                    'user_id' => $this->guard->id(),
                    'channel' => $channel,
                    'action' => 'unsubscribe',
                    'correlation_id' => $correlationId,
                ]);
                return $this->successResponse(
                    data: [
                        'channel' => $channel,
                        'status' => 'unsubscribed',
                    ],
                    message: 'Successfully unsubscribed from channel',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Unsubscription failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Unsubscription failed',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
        /**
         * Get subscribed channels
         * GET /api/v2/realtime/channels
         *
         * @return JsonResponse
         */
        public function getChannels(): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            try {
                $userId = $this->guard->id() ?? 0;
                $pattern = "subscription:user.{$userId}:*";
                // Retrieve subscribed channels from cache using pattern scan
                $channels = [];
                try {
                    $keys = $this->redis->keys($pattern);
                    $channels = array_map(fn($k) => str_replace("subscription:user.{$userId}:", '', $k), $keys);
                } catch (\Throwable $redisEx) {
                    // Redis unavailable — return empty list gracefully
                }
                $this->logger->channel('audit')->info('Channels retrieved', [
                    'user_id' => $userId,
                    'count' => count($channels),
                    'correlation_id' => $correlationId,
                ]);
                return $this->successResponse(
                    data: [
                        'channels' => $channels,
                        'count' => count($channels),
                    ],
                    message: 'Channels retrieved',
                    correlationId: $correlationId
                );
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to retrieve channels', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->errorResponse(
                    message: 'Failed to retrieve channels',
                    statusCode: 500,
                    correlationId: $correlationId
                );
            }
        }
}
