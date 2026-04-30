<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Exceptions;

final class InvalidRideDataException extends TaxiException
{
    public function __construct(string $reason)
    {
        parent::__construct("Invalid ride data: {$reason}");
    }
}
