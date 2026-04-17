<?php declare(strict_types=1);

namespace App\Domains\Travel\Events;

use App\Domains\Travel\Models\TourBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tourism Booking Cancelled Event
 * 
 * Fired when a booking is cancelled with ML-fraud score.
 * Triggers refund processing and CRM status update.
 */
final class TourismBookingCancelledEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TourBooking $booking,
        public readonly string $reason,
        public readonly float $fraudScore,
        public readonly string $correlationId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tourism.bookings.' . $this->booking->user_id);
    }
}
