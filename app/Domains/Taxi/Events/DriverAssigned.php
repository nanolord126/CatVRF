<?php declare(strict_types=1);

namespace App\Domains\Taxi\Events;

use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Models\TaxiRide;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DriverAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TaxiRide $ride,
        public readonly TaxiDriver $driver,
        public readonly string $correlationId,
    ) {}
}
