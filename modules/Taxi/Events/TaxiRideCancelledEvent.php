<?php declare(strict_types=1);

namespace Modules\Taxi\Events;

use Modules\Taxi\Models\TaxiRide;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a taxi ride is cancelled.
 * Triggers refund processing and notifications.
 */
final readonly class TaxiRideCancelledEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public TaxiRide $ride,
        public string $reason,
        public string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('taxi.rides.' . $this->ride->passenger_id)];
        
        if ($this->ride->driver_id !== null) {
            $channels[] = new PrivateChannel('taxi.drivers.' . $this->ride->driver->user_id);
        }
        
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'ride_uuid' => $this->ride->uuid,
            'status' => $this->ride->status,
            'cancellation_reason' => $this->reason,
            'cancelled_at' => $this->ride->completed_at->toIso8601String(),
            'cancellation_fee_rubles' => $this->calculateCancellationFee(),
            'correlation_id' => $this->correlationId,
        ];
    }

    private function calculateCancellationFee(): float
    {
        $basePrice = $this->ride->final_price_kopeki / 100;
        
        if ($this->ride->status === TaxiRide::STATUS_STARTED) {
            return $basePrice * 0.3;
        }
        
        if ($this->ride->status === TaxiRide::STATUS_ACCEPTED && now()->diffInMinutes($this->ride->accepted_at) < 2) {
            return $basePrice * 0.1;
        }
        
        return 0.0;
    }
}
