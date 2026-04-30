<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Exceptions;

final class VehicleNotFoundException extends AutoException
{
    public function __construct(string $vehicleId)
    {
        parent::__construct("Vehicle with ID '{$vehicleId}' not found");
    }
}
