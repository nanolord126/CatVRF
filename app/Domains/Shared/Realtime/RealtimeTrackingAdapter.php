<?php

declare(strict_types=1);

namespace App\Domains\Shared\Realtime;

use App\Domains\Realtime\Services\RealtimeService;
use App\Domains\Shared\Realtime\Services\RealtimeScalingService;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * RealtimeTrackingAdapter — unified adapter for real-time order tracking across all verticals.
 *
 * Provides a clean interface for vertical services to:
 * - Start tracking sessions for orders
 * - Get tracking channels for buyers/couriers
 * - Broadcast location updates
 * - Handle tracking lifecycle events
 *
 * @version 2026.1
 */
final readonly class RealtimeTrackingAdapter
{
    public function __construct(
        private readonly RealtimeService $realtimeService,
        private readonly RealtimeScalingService $scalingService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Start tracking session for an order from any vertical
     *
     * @param array $orderData Order information including id, vertical, courier_id, buyer_id
     * @param string $correlationId Correlation ID for tracing
     * @return array Tracking session data with channel info
     */
    public function startTracking(array $orderData, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $orderId = $orderData['order_id'] ?? null;
        $vertical = $orderData['vertical'] ?? 'unknown';
        $courierId = $orderData['courier_id'] ?? null;
        $buyerId = $orderData['buyer_id'] ?? null;

        if (!$orderId) {
            throw new \InvalidArgumentException('order_id is required for tracking');
        }

        // Check if realtime is enabled for this vertical
        if (!$this->scalingService->isRealtimeEnabled($vertical)) {
            $this->logger->warning('Realtime tracking disabled for vertical', [
                'vertical' => $vertical,
                'order_id' => $orderId,
                'correlation_id' => $correlationId,
            ]);

            return [
                'session_id' => (string) Str::uuid(),
                'order_id' => $orderId,
                'vertical' => $vertical,
                'realtime_enabled' => false,
            ];
        }

        $buyerChannel = $this->scalingService->getBuyerChannel($vertical, $orderId);
        $courierChannel = $courierId ? $this->scalingService->getCourierChannel($vertical, $orderId) : null;

        // Broadcast tracking started event
        $this->realtimeService->broadcast(
            channel: $buyerChannel,
            event: 'tracking.started',
            data: [
                'order_id' => $orderId,
                'vertical' => $vertical,
                'courier_id' => $courierId,
                'started_at' => now()->toIso8601String(),
                'update_interval' => $this->scalingService->getUpdateInterval($vertical),
            ],
            correlationId: $correlationId,
        );

        $this->scalingService->recordStats($vertical, 'connect');

        $this->logger->info('Tracking session started', [
            'order_id' => $orderId,
            'vertical' => $vertical,
            'buyer_channel' => $buyerChannel,
            'courier_channel' => $courierChannel,
            'correlation_id' => $correlationId,
            'priority' => $this->scalingService->getPriority($vertical),
        ]);

        return [
            'session_id' => (string) Str::uuid(),
            'order_id' => $orderId,
            'vertical' => $vertical,
            'buyer_channel' => $buyerChannel,
            'courier_channel' => $courierChannel,
            'courier_id' => $courierId,
            'buyer_id' => $buyerId,
            'update_interval' => $this->scalingService->getUpdateInterval($vertical),
            'priority' => $this->scalingService->getPriority($vertical),
        ];
    }

    /**
     * Get public channel for buyer/customer to track order
     */
    public function getBuyerChannel(int|string $orderId, string $vertical): string
    {
        return $this->scalingService->getBuyerChannel($vertical, $orderId);
    }

    /**
     * Get private channel for courier to broadcast location updates
     */
    public function getCourierChannel(int|string $orderId, string $vertical): string
    {
        return $this->scalingService->getCourierChannel($vertical, $orderId);
    }

    /**
     * Get presence channel for both buyer and courier
     */
    public function getPresenceChannel(int|string $orderId, string $vertical): string
    {
        return $this->scalingService->getPresenceChannel($vertical, $orderId);
    }

    /**
     * Broadcast location update from courier
     *
     * @param int|string $orderId Order ID
     * @param string $vertical Vertical name
     * @param array $coords Location data: lat, lon, heading, speed, etc.
     * @param string $correlationId Correlation ID for tracing
     */
    public function broadcastLocationUpdate(int|string $orderId, string $vertical, array $coords, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $channel = $this->scalingService->getBuyerChannel($vertical, $orderId);

        // Check rate limit before broadcasting
        if (!$this->scalingService->checkRateLimit($vertical, 'order', $orderId)) {
            $this->logger->warning('Rate limit exceeded for location update', [
                'order_id' => $orderId,
                'vertical' => $vertical,
                'correlation_id' => $correlationId,
            ]);
            return;
        }

        $this->realtimeService->broadcast(
            channel: $channel,
            event: 'location.updated',
            data: array_merge($coords, [
                'order_id' => $orderId,
                'vertical' => $vertical,
                'timestamp' => now()->toIso8601String(),
            ]),
            correlationId: $correlationId,
        );

        $this->scalingService->recordStats($vertical, 'message');

        $this->logger->info('Location update broadcasted', [
            'order_id' => $orderId,
            'vertical' => $vertical,
            'coords' => $coords,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Broadcast courier arrival event
     */
    public function broadcastCourierArrival(int|string $orderId, string $vertical, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $channel = $this->scalingService->getBuyerChannel($vertical, $orderId);

        $this->realtimeService->broadcast(
            channel: $channel,
            event: 'courier.arrived',
            data: [
                'order_id' => $orderId,
                'vertical' => $vertical,
                'arrived_at' => now()->toIso8601String(),
            ],
            correlationId: $correlationId,
        );

        $this->scalingService->recordStats($vertical, 'message');
    }

    /**
     * Broadcast delivery completion event
     */
    public function broadcastDeliveryCompleted(int|string $orderId, string $vertical, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $channel = $this->scalingService->getBuyerChannel($vertical, $orderId);

        $this->realtimeService->broadcast(
            channel: $channel,
            event: 'delivery.completed',
            data: [
                'order_id' => $orderId,
                'vertical' => $vertical,
                'completed_at' => now()->toIso8601String(),
            ],
            correlationId: $correlationId,
        );

        $this->scalingService->recordStats($vertical, 'message');
    }

    /**
     * Broadcast ETA update
     */
    public function broadcastEtaUpdate(int|string $orderId, string $vertical, int $etaMinutes, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // Check rate limit before broadcasting
        if (!$this->scalingService->checkRateLimit($vertical, 'order', $orderId)) {
            $this->logger->warning('Rate limit exceeded for ETA update', [
                'order_id' => $orderId,
                'vertical' => $vertical,
                'correlation_id' => $correlationId,
            ]);
            return;
        }

        $channel = $this->scalingService->getBuyerChannel($vertical, $orderId);

        $this->realtimeService->broadcast(
            channel: $channel,
            event: 'eta.updated',
            data: [
                'order_id' => $orderId,
                'vertical' => $vertical,
                'eta_minutes' => $etaMinutes,
                'updated_at' => now()->toIso8601String(),
            ],
            correlationId: $correlationId,
        );

        $this->scalingService->recordStats($vertical, 'message');
    }

    /**
     * Stop tracking session for an order
     */
    public function stopTracking(int|string $orderId, string $vertical, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $channel = $this->scalingService->getBuyerChannel($vertical, $orderId);

        $this->realtimeService->broadcast(
            channel: $channel,
            event: 'tracking.stopped',
            data: [
                'order_id' => $orderId,
                'vertical' => $vertical,
                'stopped_at' => now()->toIso8601String(),
            ],
            correlationId: $correlationId,
        );

        $this->scalingService->recordStats($vertical, 'disconnect');

        $this->logger->info('Tracking session stopped', [
            'order_id' => $orderId,
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
        ]);
    }
}
