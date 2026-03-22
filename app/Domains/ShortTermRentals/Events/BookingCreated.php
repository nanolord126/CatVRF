<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Events;

use App\Domains\ShortTermRentals\Models\ApartmentBooking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BookingCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApartmentBooking $booking,
        public readonly string $correlationId
    ) {}
}
