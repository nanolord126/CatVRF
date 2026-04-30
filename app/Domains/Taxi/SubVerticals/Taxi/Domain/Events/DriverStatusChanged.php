<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Events;

use Modules\Taxi\Domain\Entities\Driver;

final readonly class DriverStatusChanged
{
    public function __construct(
        public Driver $driver,
        public string $previousStatus,
        public string $newStatus,
    ) {
    }
}
