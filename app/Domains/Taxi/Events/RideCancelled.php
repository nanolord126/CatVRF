<?php declare(strict_types=1);

namespace App\Domains\Taxi\Events;

use App\Domains\Taxi\Models\TaxiRide;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RideCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TaxiRide $ride,
        public readonly string $cancelledBy,
        public readonly string $reason,
        public readonly string $correlationId,
    ) {}
}
