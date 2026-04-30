<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Events;

use Modules\Auto\Domain\Entities\Vehicle;

final readonly class VehicleCreated
{
    public function __construct(
        public Vehicle $vehicle,
    ) {
    }
}
