<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class AppointmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $appointmentId,
        public int $venueId,
        public int $masterId,
        public int $userId,
        public int $serviceId,
        public \DateTimeImmutable $startTime,
        public float $finalPrice,
        public bool $isOnlineBooking,
    ) {
    }
}
