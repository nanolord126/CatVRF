<?php declare(strict_types=1);

namespace Modules\Taxi\Events;

use Modules\Taxi\Models\TaxiRide;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a taxi ride is completed.
 * Triggers payment processing, driver payout, and CRM integration.
 */
final readonly class TaxiRideCompletedEvent implements ShouldBroadcast
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
            'completed_at' => $this->ride->completed_at->toIso8601String(),
            'final_price_rubles' => $this->ride->final_price_kopeki / 100,
            'distance_kilometers' => round($this->ride->distance_meters / 1000, 2),
            'duration_minutes' => (int) ceil($this->ride->duration_seconds / 60),
            'rating_url' => url("/api/taxi/rides/{$this->ride->id}/rate"),
            'correlation_id' => $this->correlationId,
        ];
    }
}
