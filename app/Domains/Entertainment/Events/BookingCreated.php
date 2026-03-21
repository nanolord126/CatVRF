<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Events;

use App\Domains\Entertainment\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BookingCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $correlationId,
    ) {}
}
