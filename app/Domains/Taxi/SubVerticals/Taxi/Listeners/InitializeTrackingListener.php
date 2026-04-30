<?php

declare(strict_types=1);

namespace Modules\Taxi\Listeners;

use Modules\Taxi\Events\TaxiRideStartedEvent;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection;

/**
 * Listener for TaxiRideStartedEvent.
 * Initializes real-time tracking via WebSocket.
 */
final readonly class InitializeTrackingListener
{
    public function __construct(
        private readonly LogManager $log,
        private readonly Connection $redis,
    ) {}

    public function handle(TaxiRideStartedEvent $event): void
    {
        $this->log->channel('audit')->info('Real-time tracking initialized', [
            'ride_id' => $event->ride->id,
            'driver_id' => $event->ride->driver_id,
            'correlation_id' => $event->correlationId,
        ]);

        $this->redis->publish("taxi:tracking:{$event->ride->id}", json_encode([
            'action' => 'start_tracking',
            'ride_id' => $event->ride->id,
            'driver_id' => $event->ride->driver_id,
            'timestamp' => now()->toIso8601String(),
            'correlation_id' => $event->correlationId,
        ]));
    }
}
