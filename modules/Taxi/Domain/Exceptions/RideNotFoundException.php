<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Exceptions;

final class RideNotFoundException extends TaxiException
{
    public function __construct(string $rideId)
    {
        parent::__construct("Ride with ID '{$rideId}' not found");
    }
}
