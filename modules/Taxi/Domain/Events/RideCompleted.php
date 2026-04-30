<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Events;

use Modules\Taxi\Domain\Entities\Ride;

final readonly class RideCompleted
{
    public function __construct(
        public Ride $ride,
    ) {
    }
}
