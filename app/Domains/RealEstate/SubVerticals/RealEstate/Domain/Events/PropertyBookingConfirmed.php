<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Events;

use Modules\RealEstate\Domain\Entities\PropertyBooking;

final readonly class PropertyBookingConfirmed
{
    public function __construct(
        public PropertyBooking $booking,
    ) {
    }
}
