<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Exceptions;

final class InvalidPropertyDataException extends RealEstateException
{
    public function __construct(string $reason)
    {
        parent::__construct("Invalid property data: {$reason}");
    }
}
