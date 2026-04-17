<?php declare(strict_types=1);

namespace App\Domains\Travel\Events;

use App\Domains\Travel\Models\TourBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tourism Booking Confirmed Event
 * 
 * Fired when a booking is confirmed after payment and biometric verification.
 * Triggers video call scheduling and CRM status update.
 */
final class TourismBookingConfirmedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TourBooking $booking,
        public readonly string $correlationId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tourism.bookings.' . $this->booking->user_id);
    }
}
