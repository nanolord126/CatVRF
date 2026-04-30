<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class AppointmentCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $appointmentId,
        public int $venueId,
        public int $masterId,
        public int $userId,
        public string $reason,
    ) {
    }
}
