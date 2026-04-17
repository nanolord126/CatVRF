<?php declare(strict_types=1);

namespace Modules\Taxi\Events;

use Modules\Taxi\Models\TaxiRide;
use Modules\Taxi\Models\TaxiDriver;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a driver is matched to a ride.
 * Triggers push notifications to passenger and driver.
 */
final readonly class TaxiDriverMatchedEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public TaxiRide $ride,
        public TaxiDriver $driver,
        public string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('taxi.rides.' . $this->ride->passenger_id),
            new PrivateChannel('taxi.drivers.' . $this->driver->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'ride_uuid' => $this->ride->uuid,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'driver_rating' => $this->driver->rating,
            'vehicle_plate' => $this->driver->vehicles->first()->license_plate ?? null,
            'eta_minutes' => $this->calculateETA(),
            'correlation_id' => $this->correlationId,
        ];
    }

    private function calculateETA(): int
    {
        return (int) ceil($this->ride->duration_seconds / 60);
    }
}
