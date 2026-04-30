<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Exceptions;

final class PropertyNotFoundException extends RealEstateException
{
    public function __construct(string $propertyId)
    {
        parent::__construct("Property with ID '{$propertyId}' not found");
    }
}
