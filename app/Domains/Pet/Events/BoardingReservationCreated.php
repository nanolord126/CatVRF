<?php declare(strict_types=1);

namespace App\Domains\Pet\Events;

use App\Domains\Pet\Models\PetBoardingReservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BoardingReservationCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly PetBoardingReservation $reservation,
        public readonly string $correlationId,
    ) {}
}
