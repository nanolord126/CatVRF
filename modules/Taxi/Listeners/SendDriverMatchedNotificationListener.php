<?php declare(strict_types=1);

namespace Modules\Taxi\Listeners;

use Modules\Taxi\Events\TaxiDriverMatchedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Listener for TaxiDriverMatchedEvent.
 * Sends push notifications to passenger and driver via WebSocket.
 */
final readonly class SendDriverMatchedNotificationListener
{
    public function handle(TaxiDriverMatchedEvent $event): void
    {
        Log::channel('audit')->info('Driver matched notification sent', [
            'ride_id' => $event->ride->id,
            'driver_id' => $event->driver->id,
            'passenger_id' => $event->ride->passenger_id,
            'correlation_id' => $event->correlationId,
        ]);

        Redis::publish('taxi:notifications', json_encode([
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
