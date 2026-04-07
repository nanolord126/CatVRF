<?php

declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use App\Domains\Auto\Models\ParkingBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
final class ParkingBookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly ParkingBooking $booking,
        public readonly string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->logger->info('Parking booking created.', [
            'correlation_id' => $this->correlationId,
            'booking_id' => $this->booking->id,
            'tenant_id' => $this->booking->tenant_id,
            'client_id' => $this->booking->client_id,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->booking->tenant_id),
            new PrivateChannel('user.' . $this->booking->client_id),
        ];
    }

    /**
     * The name of the event's broadcast.
     */
    public function broadcastAs(): string
    {
        return 'parking.booking.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'spot_number' => $this->booking->spot_number,
            'starts_at' => $this->booking->starts_at->toIso8601String(),
            'ends_at' => $this->booking->ends_at->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ];
    }
}
