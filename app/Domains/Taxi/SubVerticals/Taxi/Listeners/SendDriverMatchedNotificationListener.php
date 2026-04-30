<?php

declare(strict_types=1);

namespace Modules\Taxi\Listeners;

use Modules\Taxi\Events\TaxiDriverMatchedEvent;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection;

/**
 * Listener for TaxiDriverMatchedEvent.
 * Sends push notifications to passenger and driver via WebSocket.
 */
final readonly class SendDriverMatchedNotificationListener
{
    public function __construct(
        private readonly LogManager $log,
        private readonly Connection $redis,
    ) {}

    public function handle(TaxiDriverMatchedEvent $event): void
    {
        $this->log->channel('audit')->info('Driver matched notification sent', [
            'ride_id' => $event->ride->id,
            'driver_id' => $event->driver->id,
            'passenger_id' => $event->ride->passenger_id,
            'correlation_id' => $event->correlationId,
        ]);

        $this->redis->publish('taxi:notifications', json_encode([
            'type' => 'driver_matched',
            'ride_id' => $event->ride->id,
            'passenger_id' => $event->ride->passenger_id,
            'driver_id' => $event->driver->id,
            'driver_name' => $event->driver->full_name,
            'vehicle_plate' => $event->driver->vehicles->first()->license_plate ?? null,
            'eta_minutes' => $event->calculateETA(),
            'correlation_id' => $event->correlationId,
        ]));
    }
}
