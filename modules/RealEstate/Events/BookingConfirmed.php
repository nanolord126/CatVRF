<?php declare(strict_types=1);

namespace Modules\RealEstate\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\RealEstate\Models\PropertyBooking;

final class BookingConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PropertyBooking $booking,
        public string $correlationId,
    ) {}
}
