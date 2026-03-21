<?php declare(strict_types=1);

namespace App\Domains\Travel\Events;

use App\Domains\Travel\Models\TravelTransportation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TransportationBooked implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public TravelTransportation $transportation,
        public string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('travel.transportation'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transportation.booked';
    }
}
