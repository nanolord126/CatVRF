<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use App\Domains\Auto\Models\CarWashBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
/**
 * Class CarWashBookingCancelled
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Auto\Events
 */
final class CarWashBookingCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CarWashBooking $booking,
        public readonly string $reason,
        public readonly string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->logger->info('CarWashBookingCancelled event dispatched', [
            'correlation_id' => $this->correlationId,
            'booking_id' => $this->booking->id,
            'reason' => $this->reason,
        ]);
    }

    /**
     * Handle broadcastOn operation.
     *
     * @throws \DomainException
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->booking->tenant_id),
            new PrivateChannel('user.' . $this->booking->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'car-wash.booking.cancelled';
    }
}
