<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Realtime;

use App\Http\Controllers\Api\BaseApiV2Controller;
use App\Services\RealtimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller: Real-Time Channel Subscriptions
 * 
 * Endpoints:
 * - POST /api/v2/realtime/subscribe - Subscribe to channel
 * - DELETE /api/v2/realtime/unsubscribe - Unsubscribe from channel
 * - GET /api/v2/realtime/channels - Get subscribed channels
 * 
 * @package App\Http\Controllers\Api\V2\Realtime
 */
final class SubscriptionController extends BaseApiV2Controller
{
    public function __construct(
        private readonly RealtimeService $realtimeService,
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
        $correlationId = (string) Str::uuid();

        try {
            $request->validate([
                'channel' => 'required|string|max:255',
            ]);

            $channel = $request->get('channel');

            $this->realtimeService->subscribe(
                userId: auth()->id() ?? 0,
                channel: $channel
            );

            Log::channel('audit')->info('Channel subscription', [
                'user_id' => auth()->id(),
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
            Log::channel('audit')->error('Subscription failed', [
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
        $correlationId = (string) Str::uuid();

        try {
            $request->validate([
                'channel' => 'required|string|max:255',
            ]);

            $channel = $request->get('channel');

            $this->realtimeService->unsubscribe(
                userId: auth()->id() ?? 0,
                channel: $channel
            );

            Log::channel('audit')->info('Channel unsubscription', [
                'user_id' => auth()->id(),
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
            Log::channel('audit')->error('Unsubscription failed', [
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
        $correlationId = (string) Str::uuid();

        try {
            $userId = auth()->id() ?? 0;
            $pattern = "subscription:user.{$userId}:*";

            // Get all subscribed channels from cache
            $channels = [];
            // Note: In production use proper cache driver with pattern matching
            // For now: return empty array as placeholder

            Log::channel('audit')->info('Channels retrieved', [
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
            Log::channel('audit')->error('Failed to retrieve channels', [
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
