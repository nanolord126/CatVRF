<?php declare(strict_types=1);

namespace Modules\Taxi\Listeners;

use Modules\Taxi\Events\TaxiRideStartedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Listener for TaxiRideStartedEvent.
 * Initializes real-time tracking via WebSocket.
 */
final readonly class InitializeTrackingListener
{
    public function handle(TaxiRideStartedEvent $event): void
    {
        Log::channel('audit')->info('Real-time tracking initialized', [
            'ride_id' => $event->ride->id,
            'driver_id' => $event->ride->driver_id,
            'correlation_id' => $event->correlationId,
        ]);

        Redis::publish("taxi:tracking:{$event->ride->id}", json_encode([
            'action' => 'start_tracking',
            'ride_id' => $event->ride->id,
            'driver_id' => $event->ride->driver_id,
            'timestamp' => now()->toIso8601String(),
            'correlation_id' => $event->correlationId,
        ]));
    }
}
