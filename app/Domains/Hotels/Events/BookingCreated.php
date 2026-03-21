<?php declare(strict_types=1);

namespace App\Domains\Hotels\Events;

use App\Domains\Hotels\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BookingCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
        public readonly string $correlationId = '',
    ) {}
}
