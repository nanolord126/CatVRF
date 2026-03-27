<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Events;

use App\Domains\ShortTermRentals\Models\StrBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * КАНОН 2026: Событие создания бронирования
 */
final class StrBookingCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly StrBooking $booking,
        public readonly string $correlationId
    ) {}
}
