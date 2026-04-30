<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Events;

use Modules\Auto\Domain\Entities\VehicleMaintenance;

final readonly class VehicleMaintenanceScheduled
{
    public function __construct(
        public VehicleMaintenance $maintenance,
    ) {
    }
}
