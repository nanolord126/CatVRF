<?php

declare(strict_types=1);

namespace App\Domains\Sports\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BookingConfirmedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $bookingId,
        public readonly int $userId,
        public readonly int $venueId,
        public readonly ?int $trainerId,
        public readonly string $slotStart,
        public readonly string $slotEnd,
        public readonly string $bookingType,
        public readonly string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        return [];
    }
}
