<?php

declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use App\Domains\Auto\Models\CarWashBooking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
final class CarWashCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly CarWashBooking $booking,
        public readonly string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->logger->info('CarWashCompleted event dispatched', [
            'correlation_id' => $this->correlationId,
            'booking_id' => $this->booking->id,
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
            new Channel('tenant.' . $this->booking->tenant_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'car-wash.booking.completed';
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
            'wash_type' => $this->booking->wash_type,
            'completed_at' => $this->booking->completed_at?->toIso8601String(),
            'price' => $this->booking->price,
            'correlation_id' => $this->correlationId,
        ];
    }
}
