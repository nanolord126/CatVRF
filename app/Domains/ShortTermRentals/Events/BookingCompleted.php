<?php

declare(strict_types=1);


namespace App\Domains\ShortTermRentals\Events;

use App\Domains\ShortTermRentals\Models\ApartmentBooking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * BookingCompleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BookingCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApartmentBooking $booking,
        public readonly string $correlationId
    ) {}
}
