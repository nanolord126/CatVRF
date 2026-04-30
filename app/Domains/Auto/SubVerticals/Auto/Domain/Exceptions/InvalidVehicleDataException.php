<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Exceptions;

final class InvalidVehicleDataException extends AutoException
{
    public function __construct(string $reason)
    {
        parent::__construct("Invalid vehicle data: {$reason}");
    }
}
