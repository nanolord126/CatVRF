<?php declare(strict_types=1);

namespace Modules\Taxi\Events;

use Modules\Taxi\Models\TaxiRide;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a taxi ride is created.
 * Triggers listeners for AI analysis, notifications, and CRM integration.
 */
final readonly class TaxiRideCreatedEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public TaxiRide $ride,
        public string $correlationId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('taxi.rides.' . $this->ride->passenger_id);
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'ride_uuid' => $this->ride->uuid,
            'status' => $this->ride->status,
            'correlation_id' => $this->correlationId,
        ];
    }
}
