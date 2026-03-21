<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RideCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $rideId,
        public readonly string $driverId,
        public readonly string $passengerId,
        public readonly string $correlationId,
        public readonly array $metadata = [],
    ) {}
}
